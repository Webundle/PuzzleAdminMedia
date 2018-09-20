<?php

namespace Puzzle\Admin\MediaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('puzzle_admin_media');
        
        $rootNode
            ->children()
                ->scalarNode('title')->defaultValue('media.title')->end()
                ->scalarNode('description')->defaultValue('media.description')->end()
                ->scalarNode('icon')->defaultValue('media.icon')->end()
                ->arrayNode('roles')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('media')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('label')->defaultValue('ROLE_MEDIA')->end()
                                ->scalarNode('description')->defaultValue('media.role.default')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('upload_dir')->end()
                ->scalarNode('max_size')->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
