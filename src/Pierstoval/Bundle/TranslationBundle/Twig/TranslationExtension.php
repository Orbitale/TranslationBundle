<?php

namespace Pierstoval\Bundle\TranslationBundle\Twig;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class Translation
 * Project pierstoval
 *
 * @author Pierstoval
 * @version 1.0 11/01/2014
 */
class TranslationExtension extends \Twig_Extension {

    function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    public function getName() {
        return 'pierstoval_translation_extension';
    }

    public function getFilters() {
        return array(
            'trans' => new \Twig_Filter_Method($this, 'transFilter'),
        );
    }

    /**
     * Fonctions Twig
     */
    public function getFunctions() {
        return array(
            'translationDomain' => new \Twig_Function_Method($this, 'translationDomainFunction'),
        );
    }

    public function translationDomainFunction($domain = null) {
        $this->translator->translationDomain($domain);
    }


    public function transFilter($message, array $arguments = array(), $domain = null, $locale = null) {
        return $this->translator->trans($message, $arguments, $domain, $locale);
    }
}
