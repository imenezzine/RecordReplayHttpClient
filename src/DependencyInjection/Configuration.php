<?php

namespace Symfony\HttpClientRecorderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('http_client_recorder');

        $tree->getRootNode()
            ->canBeEnabled()
            ->children()
            ->scalarNode('cassette_path')->defaultValue('%kernel.project_dir%/tests/cassettes')->end()
            ->enumNode('mode')
            ->values(['replay', 'record', 'replay_or_record'])
            ->defaultValue('replay_or_record')
            ->end()
            ->end();

        return $tree;
    }
}
