<?php
/*
* This file is part of the OrbitaleTranslationBundle package.
*
* (c) Alexandre Rock Ancelet <contact@orbitale.io>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Orbitale\Bundle\TranslationBundle\Listeners;

use Orbitale\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class FlushTranslations implements EventSubscriberInterface {

    protected $translator;

    function __construct(Translator $translator) {
        $this->translator = $translator;
    }

    function flushTranslations(){
        $this->translator->flushTranslations();
    }

    /**
     * {@inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::FINISH_REQUEST => array('flushTranslations'),
            KernelEvents::TERMINATE => array('flushTranslations'),
            KernelEvents::EXCEPTION => array('flushTranslations')
        );
    }

}
