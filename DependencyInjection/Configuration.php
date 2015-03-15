<?php
/*
* This file is part of the OrbitaleTranslationBundle package.
*
* (c) Alexandre Rock Ancelet <contact@orbitale.io>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Orbitale\Bundle\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('orbitale_translation');

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
