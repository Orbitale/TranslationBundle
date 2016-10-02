<?php

namespace Orbitale\Bundle\TranslationBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Orbitale\Bundle\TranslationBundle\Translation\Translator;

/**
 * TranslationDataCollector.
 *
 * @author Adrian Olek <adrianolek@gmail.com>
 * @link https://github.com/adrianolek/AOTranslationBundle
 */
class TranslationDataCollector extends DataCollector
{

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param Translator $translator The Symfony Translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data['tokens'] = array();
        $this->data['unatranslated_count'] = 0;

        foreach ($this->translator->getRequestedTokens() as $token => $translated) {
            $this->data['tokens'][] = $token;
            if (!$translated) {
                $this->data['unatranslated_count']++;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'orbitale_translation';
    }

    /**
     * Returns message names grouped by domain
     * @return array The message names array
     */
    public function getTokens()
    {
        return $this->data['tokens'];
    }

    /**
     * Returns total number of translations in all domains
     * @return number
     */
    public function getCount()
    {
        return count($this->data['tokens']);
    }

    /**
     * Returns number of objects without translation in current locale
     * @return number
     */
    public function getUntranslated()
    {
        return $this->data['unatranslated_count'];
    }
}
