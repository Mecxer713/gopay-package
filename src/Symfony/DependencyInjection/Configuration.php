<?php

namespace Gooomart\GoPay\Symfony\DependencyInjection;

use Gooomart\GoPay\GoPayService;
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
                ->scalarNode('payout_secret_key')->defaultValue('')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
