<?php

namespace Pierstoval\Bundle\TranslationBundle\Translation;

use Pierstoval\Bundle\TranslationBundle\Entity\Translation as Translation;

use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class Translator
 * Project pierstoval
 *
 * @author Pierstoval
 * @version 1.0 08/01/2014
 */
class Translator extends BaseTranslator implements TranslatorInterface {

    private static $catalogue = array();
    private static $temporary_domain = null;

    function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, \Symfony\Component\Translation\MessageSelector $selector, $loaderIds = array(), array $options = array()) {
        if ($container->isScopeActive('request')) {
            $locale = $container->get('session')->get('_locale');
            if (!$locale) { $locale = $container->getParameter('locale'); }
            if (!$locale) { $locale = $container->getParameter('fallback_locale'); }
            if (!$locale) { $locale = $container->get('request')->get('locale'); }
            if (!$locale) { $locale = 'fr'; }
            $locale = preg_replace('#_.*$#isUu', '', $locale);
            $container->get('session')->set('_locale', $locale);
            $container->get('request')->setLocale($locale);
        }
        return parent::__construct($container, $selector, $loaderIds, $options);
    }

    public static function temporaryDomain($domain = null) {
        self::$temporary_domain = $domain;
    }

    public function translationDomain($domain = null) {
        self::temporaryDomain($domain);
    }

    public function translate($id, array $parameters = array(), $domain = null, $locale = null) {
        return $this->trans($id, $parameters, $domain, $locale);
    }

    public function getLangs() {
        return $this->container->get('doctrine')->getManager()
            ->getRepository('PierstovalTranslationBundle:Languages')
            ->findAll();
    }

    /**
     * Recherche dans le moteur natif de Symfony2 si une traduction existe pour l'id donné
     *
     * @param string $locale
     * @param string $id
     * @param string $domain
     * @return string|null
     */
    function findInNativeCatalogue($locale, $id, $domain) {
        if (!isset($this->catalogues[$locale])) {
            $this->loadCatalogue($locale);
        }
        return $this->catalogues[$locale]->has($id, $domain)
            && $this->catalogues[$locale]->get($id, $domain)
             ? $this->catalogues[$locale]->get($id, $domain)
             : null;
    }

    /**
    * {@inheritdoc}
    */
    public function trans($id, array $parameters = array(), $domain = null, $locale = null){

        if (
            !$id
            ||
            (is_string($id) && !trim($id))
            ||
            (is_object($id) && !trim($id->__toString()))
            ) {
            return $id;
        }

        if (!$locale) { $locale = $this->getLocale(); }
        if (!$locale) { $locale = $this->container->get('request')->get('locale'); }
        if (!$locale) { $locale = 'fr'; }

        if (!$domain && self::$temporary_domain) {
            $domain = self::$temporary_domain;
        }

        if (!$domain) { $domain = 'messages'; }

        $translation = $this->findInNativeCatalogue($locale, $id, $domain);

        if (null === $translation) {

            // Génère le catalogue BDD à partir de la locale et du domaine
            $this->loadDbCatalogue($locale, $domain);

            $token = md5($id.'_'.$domain.'_'.$locale);
            $translation = $this->findToken($token);

            if ($translation) {
                if ($translation->getTranslation()) {
                    $translation = $translation->getTranslation();
                } else {
                    $translation = $id;
                }
            } else {
                $em = $this->container->get('doctrine')->getManager();
                $translation = new Translation();
                $translation
                    ->setToken($token)
                    ->setSource($id)
                    ->setDomain($domain)
                    ->setLocale($locale);
                $em->persist($translation);
                $em->flush();
                self::$catalogue[$locale][$domain][$token] = $translation;
                $translation = $id;
            }
        }

//        exit(strtr($translation, $parameters));
        return strtr($translation, $parameters);
    }

    protected function findToken($token) {
        $catalogue = self::$catalogue;
        foreach ($catalogue as $locale_catalogue) {
            foreach ($locale_catalogue as $domain_catalogue) {
                if (isset($domain_catalogue[$token])) {
                    return $domain_catalogue[$token];
                }
            }
        }
        return null;
    }

    protected function loadDbCatalogue($locale, $domain){
        $catalogue = self::$catalogue;

        if (!isset($catalogue[$locale][$domain])) {
            $translations = $this->container->get('doctrine')->getManager()
                ->getRepository('PierstovalTranslationBundle:Translation')
                ->findBy(array('locale'=>$locale,'domain'=>$domain));


    //        if (!isset(self::$catalogue[$locale][$domain])) {
    //            self::$catalogue[$locale][$domain] = array();
    //        }

            if ($translations) {
                foreach ($translations as $translation) {
                    self::$catalogue[$locale][$translation->getDomain()][$translation->getToken()] = $translation;
                }
            }
        }
    }

}
