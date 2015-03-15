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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OrbitaleTranslationExtension extends Extension
{

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('parameters.yml');
        $loader->load('default_locales.yml');

        if (isset($config['locales']) && !empty($config['locales'])) {
            $config['locales'] = $this->processLocales($container, $config['locales']);
        } else {
            $config['locales'] = $container->getParameter('locales');
            $containerLocale = $container->getParameter('locale');
            if ($containerLocale && isset($config['locales'][$containerLocale])) {
                $config['locales'] = array($containerLocale => $config['locales'][$containerLocale]);
            }
        }

        if ($config['use_sonata']) {
            $loader->load('sonata_admin.yml');
        }

        foreach ($config as $key => $value) {
            $container->setParameter('orbitale_translation.'.$key, $value);
        }

        $container->setParameter('locales', $config['locales']);

        $loader->load('services.yml');

    }

    /**
     * Gets user locales configuration in config file, and validates it.
     *
     * @param ContainerBuilder $container
     * @param $locales
     * @return array
     * @throws InvalidArgumentException
     */
    private function processLocales(ContainerBuilder $container, $locales)
    {
        if (is_string($locales)) {
            // When config similar to:
            // locales: 'fr,de,en'
            $locales = array_map('trim', explode(',', $locales));
        }

        $overrideLocales = array();

        $baseLocales = $container->getParameter('locales');

        foreach ($locales as $locale => $publicName) {
            if (
                (!is_numeric($locale) && !in_array($locale, array_keys($baseLocales)))
                || (is_numeric($locale) && !in_array($publicName, array_keys($baseLocales)))
            ) {
                $msg = 'An error occured when parsing configuration for Translation locales.'."\n"
                    .'You specified a locale named "'.(is_numeric($locale) ? $publicName : $locale).'" which is not registered in our supported locales.'."\n"
                    .'Locale format must be the shortest, for example, you should use "en" instead of "en_US".';
                throw new InvalidArgumentException($msg);
            } else {
                if (is_numeric($locale)) {
                    // When config similar to:
                    // locales : ['fr', 'de', 'en']
                    $overrideLocales[$publicName] = $baseLocales[$publicName];
                } else {
                    // When config similar to:
                    // locales : { "fr": "Français", "de": "Deutsch", "en": "English" }
                    $overrideLocales[$locale] = $publicName;
                }
            }
        }

        $container->setParameter('locales', $overrideLocales);

        return $overrideLocales;
    }

}
