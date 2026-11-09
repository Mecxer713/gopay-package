<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Symfony\DependencyInjection;

use Mecxer713\GoPay\GoPayService;
use Mecxer713\GoPay\GoPayServiceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class GoPayExtension extends Extension
{
    /**
     * @param  array<int, array<string, mixed>>  $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration;
        $config = $this->processConfiguration($configuration, $configs);

        $definition = new Definition(GoPayService::class);
        $definition->setArguments([
            '$baseUrl' => $config['base_url'],
            '$paymentApiKey' => $config['api_key'],
            '$paymentSecretKey' => $config['secret_key'],
            '$payoutApiKey' => $config['payout_api_key'],
            '$maxRetries' => $config['max_retries'],
            '$retryBaseDelayMs' => $config['retry_base_delay_ms'],
        ]);
        $definition->setPublic(true);

        $container->setDefinition(GoPayService::class, $definition);
        // Autowiring par interface : `public function __construct(GoPayServiceInterface $gopay)`
        $container->setAlias(GoPayServiceInterface::class, GoPayService::class)->setPublic(true);
        $container->setAlias('gopay', GoPayService::class)->setPublic(true);
    }

    public function getAlias(): string
    {
        return 'go_pay';
    }
}
