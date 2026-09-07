<?php

namespace App\Providers;

use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\DB;
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
        DB::prohibitDestructiveCommands(
            ! app()->runningUnitTests()
            && (app()->isProduction() || config('database.prohibit_destructive_commands')),
        );

        if (PHP_OS_FAMILY === 'Windows' && ! extension_loaded('openssl')) {
            DevCommands::register(
                sprintf(
                    'php -d extension=openssl -S 127.0.0.1:%d -t public scripts/server.php',
                    (int) env('SERVER_PORT', 2080),
                ),
                'server',
            )->blue();
            DevCommands::register(
                'php -d extension=openssl artisan queue:listen --tries=1 --timeout=0',
                'queue',
            )->purple();
        }
    }
}
