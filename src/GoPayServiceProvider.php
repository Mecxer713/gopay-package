<?php

namespace Gooomart\GoPay;

use Illuminate\Support\ServiceProvider;

class GoPayServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/gopay.php' => config_path('gopay.php'),
            ], 'config');
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/gopay.php', 'gopay');

        $this->app->singleton('gopay', function ($app) {
            $config = $app['config']->get('gopay');
            
            return new GoPayService(
                $config['base_url'] ?? 'https://gopay.gooomart.com',
                $config['api_key'] ?? '',
                $config['secret_key'] ?? '',
                $config['payout_api_key'] ?? '',
                $config['payout_secret_key'] ?? ''
            );
        });
    }
}
