<?php

namespace Pierstoval\Bundle\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

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
        $rootNode = $treeBuilder->root('pierstoval_translation');

        $rootNode
            ->children()
                ->booleanNode('use_sonata')->defaultFalse()->end()
                ->scalarNode('admin_layout')->defaultNull()->end()
                ->scalarNode('output_directory')->defaultNull()->end()
                ->variableNode('locales')
                    ->validate()
                    ->always(function ($v) {
                        if (!empty($v) && (is_string($v) || is_array($v))) { return $v; }
                        throw new InvalidTypeException();
                    })
                ->end()
            ->end();

        return $treeBuilder;
    }
}
