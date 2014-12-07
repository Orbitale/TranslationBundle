<?php

namespace Pierstoval\Bundle\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PierstovalTranslationExtension extends Extension
{

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('parameters.yml');
        $loader->load('services.yml');
        $loader->load('default_locales.yml');

        if (isset($config['locales']) && !empty($config['locales'])) {
            $config['locales'] = $this->processLocales($container, $config['locales']);
        }

        if ($config['use_sonata']) {
            $loader->load('sonata_admin.yml');
        }

        foreach ($config as $key => $value) {
            $container->setParameter('pierstoval_translation.' . $key, $value);
        }

    }


    /**
     * Gets user locales configuration in config file, and validates it.
     *
     * @param ContainerBuilder $container
     * @param $locales
     * @return array
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
                $msg = 'An error occured when parsing configuration for Translation locales.' . "\n"
                    . 'You specified a locale named "' . (is_numeric($locale) ? $publicName : $locale) . '" which is not registered in our supported locales.' . "\n"
                    . 'Locale format must be the shortest, for example, you should use "en" instead of "en_US".';
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
