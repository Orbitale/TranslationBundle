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
     * @var array
     */
    protected $backends = array('native', 'easyadmin', 'sonata');

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('orbitale_translation');

        $rootNode
            ->children()
                ->scalarNode('admin_backend')
                    ->defaultNull()
                    ->validate()
                    ->always(function($v) {
                        if (null === $v) {
                            $v = 'native';
                        }
                        if (!in_array($v, $this->backends)) {
                            throw new InvalidTypeException(sprintf(
                                "Invalid admin_backend parameter \"%s\"\n".
                                "Available are the following:\n%s",
                                $v, implode(',', $this->backends
                            )));
                        }
                        return strtolower($v);
                    })->end()
                ->end()
                ->scalarNode('admin_layout')->defaultNull()->end()
                ->scalarNode('output_directory')->defaultNull()->end()
                ->variableNode('locales')
                    ->validate()
                    ->always(function ($v) {
                        if (empty($v)) {
                            throw new InvalidTypeException('Locales configuration must be set under "orbitale_translation".');
                        }
                        if (!is_string($v) && !is_array($v)) {
                            throw new InvalidTypeException(sprintf(
                                "Locales configuration must be either a comma-separated locales list,\n".
                                "an plain array of locales, or an object with key=>value matching locale=>languageName.\n" .
                                "\"%s\" given.",
                                $v
                            ));
                        }
                        return strtolower($v);
                    })
                ->end()
            ->end();

        return $treeBuilder;
    }
}
