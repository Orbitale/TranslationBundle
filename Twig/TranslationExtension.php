<?php

namespace Pierstoval\Bundle\TranslationBundle\Twig;

use Pierstoval\Bundle\TranslationBundle\Translation\Translator;

class TranslationExtension extends \Twig_Extension {

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function getName()
    {
        return 'pierstoval_translation.twig.extension';
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('langs', array($this, 'getLangs')),
        );
    }

    /**
     * @return array
     */
    public function getLangs()
    {
        return $this->translator->getLangs();
    }

}
