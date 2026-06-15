<?php

declare(strict_types=1);

namespace Mecxer713\GoPay;

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
                __DIR__.'/../config/gopay.php' => config_path('gopay.php'),
            ], 'gopay-config');
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/gopay.php', 'gopay');

        $this->app->singleton(GoPayServiceInterface::class, function ($app) {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('gopay');

            return new GoPayService(
                baseUrl:          $config['base_url']       ?? 'https://gopay.gooomart.com',
                paymentApiKey:    $config['api_key']        ?? '',
                paymentSecretKey: $config['secret_key']     ?? '',
                payoutApiKey:     $config['payout_api_key'] ?? ''
            );
        });

        $this->app->alias(GoPayServiceInterface::class, 'gopay');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [GoPayServiceInterface::class, 'gopay'];
    }
}
