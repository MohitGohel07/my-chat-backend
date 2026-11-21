<?php

namespace App\Providers;

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
        if (config('database.default') === 'sqlite') {
            $database = config('database.connections.sqlite.database');
            if ($database && $database !== ':memory:' && is_dir(dirname($database))) {
                if (! file_exists($database)) {
                    @touch($database);
                }

                // Auto-run migrations if clipper tables are not yet initialized on container boot
                if (! \Illuminate\Support\Facades\Schema::hasTable('clipper_logs') || ! \Illuminate\Support\Facades\Schema::hasTable('users')) {
                    try {
                        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                    } catch (\Throwable $e) {
                        // Log or ignore during build phase
                    }
                }
            }
        }
    }
}
