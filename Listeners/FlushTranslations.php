<?php

namespace Pierstoval\Bundle\TranslationBundle\Listeners;

use Pierstoval\Bundle\TranslationBundle\Translation\Translator;
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
