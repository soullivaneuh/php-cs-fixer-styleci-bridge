<?php

namespace SLLH\StyleCIBridge\StyleCI;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('styleci');

        $validFixers = array_merge(Fixers::$valid, array_keys(Fixers::$aliases));

        $rootNode
            ->children()
                ->enumNode('preset')
                    ->isRequired()
                    ->values(array_keys(Fixers::getPresets()))
                ->end()
                ->booleanNode('linting')
                    ->defaultTrue()
                ->end()
                ->arrayNode('enabled')
                    ->prototype('scalar')
                        ->validate()
                            ->ifNotInArray($validFixers)
                            ->thenInvalid('Invalid enabled fixer %s.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('disabled')
                    ->prototype('scalar')
                        ->validate()
                            ->ifNotInArray($validFixers)
                            ->thenInvalid('Invalid disabled fixer %s.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('finder')
                    ->children()
                        ->arrayNode('exclude')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('name')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('not_name')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('contains')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('not_contains')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('path')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('not_path')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('depth')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
