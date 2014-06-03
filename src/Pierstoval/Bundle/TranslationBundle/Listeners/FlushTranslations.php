<?php

namespace Pierstoval\Bundle\TranslationBundle\Listeners;

use Pierstoval\Bundle\TranslationBundle\Translation\Translator;

class FlushTranslations {

    protected $translator;

    function __construct(Translator $translator) {
        $this->translator = $translator;
    }

    function onKernelFinishRequest(){
        $this->translator->flushTranslations();
    }

} 