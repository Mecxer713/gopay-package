<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Laravel;

use Illuminate\Support\ServiceProvider;
use Mecxer713\GoPay\GoPayService;
use Mecxer713\GoPay\GoPayServiceInterface;

class GoPayServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/gopay.php' => config_path('gopay.php'),
            ], 'gopay-config');
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/gopay.php', 'gopay');

        $this->app->singleton(GoPayServiceInterface::class, function ($app) {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('gopay');

            return new GoPayService(
                baseUrl: (string) ($config['base_url'] ?? 'https://gopay.gooomart.com'),
                paymentApiKey: (string) ($config['api_key'] ?? ''),
                paymentSecretKey: (string) ($config['secret_key'] ?? ''),
                payoutApiKey: (string) ($config['payout_api_key'] ?? ''),
                maxRetries: (int) ($config['max_retries'] ?? 2),
                retryBaseDelayMs: (int) ($config['retry_base_delay_ms'] ?? 300)
            );
        });

        $this->app->alias(GoPayServiceInterface::class, 'gopay');
        $this->app->alias(GoPayServiceInterface::class, GoPayService::class);
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
