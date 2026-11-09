<?php

namespace Mecxer713\GoPay\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('go_pay');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('base_url')->defaultValue('https://gopay.gooomart.com')->end()
            ->scalarNode('api_key')->defaultValue('')->end()
            ->scalarNode('secret_key')->defaultValue('')->end()
            ->scalarNode('payout_api_key')->defaultValue('')->end()
            ->integerNode('max_retries')->defaultValue(2)->min(0)->end()
            ->integerNode('retry_base_delay_ms')->defaultValue(300)->min(0)->end()
            ->end();

        return $treeBuilder;
    }
}
