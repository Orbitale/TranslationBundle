<?php
/*
* This file is part of the OrbitaleTranslationBundle package.
*
* (c) Alexandre Rock Ancelet <contact@orbitale.io>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Orbitale\Bundle\TranslationBundle\Twig;

use Orbitale\Bundle\TranslationBundle\Translation\Translator;

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
        return 'orbitale_translation.twig.extension';
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
