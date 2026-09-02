<?php

namespace App\Providers;

use Illuminate\Foundation\DevCommands;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DevCommands::except('vite');
        DevCommands::register('python -u scripts/biometrico.py', 'biometric')->green();

        if (PHP_OS_FAMILY === 'Windows' && ! extension_loaded('openssl')) {
            DevCommands::register(
                'php -d extension=openssl -S 127.0.0.1:8000 -t public scripts/server.php',
                'server',
            )->blue();
            DevCommands::register(
                'php -d extension=openssl artisan queue:listen --tries=1 --timeout=0',
                'queue',
            )->purple();
        }
    }
}
