<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Symfony\DependencyInjection;

use Mecxer713\GoPay\GoPayService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class GoPayExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration;
        $config = $this->processConfiguration($configuration, $configs);

        $definition = new Definition(GoPayService::class, [
            $config['base_url'],
            $config['api_key'],
            $config['secret_key'],
            $config['payout_api_key'],
        ]);

        $container->setDefinition(GoPayService::class, $definition);
        $container->setAlias('gopay', GoPayService::class);
    }
}
