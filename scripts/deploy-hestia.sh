#!/usr/bin/env bash

set -Eeuo pipefail

project_directory="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_directory"

if [[ "${1:-}" != "--backup-confirmed" ]]; then
    echo "Deployment cancelled: create or verify a TrueNAS database backup first."
    echo "Run again with --backup-confirmed after confirming the backup."
    exit 1
fi

if [[ ! -f .env ]]; then
    echo "Deployment cancelled: the production .env file is missing."
    exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
    echo "Deployment cancelled: the server checkout contains uncommitted files."
    exit 1
fi

for required_command in git php composer npm; do
    if ! command -v "$required_command" >/dev/null 2>&1; then
        echo "Deployment cancelled: $required_command is not installed or is not in PATH."
        exit 1
    fi
done

git pull --ff-only origin main
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci --no-audit --no-fund
npm run build

php artisan down --retry=60

restore_application() {
    php artisan up
}

trap restore_application EXIT

php artisan migrate --force
php artisan optimize
php artisan reload

php artisan up
trap - EXIT

php artisan about --only=environment
