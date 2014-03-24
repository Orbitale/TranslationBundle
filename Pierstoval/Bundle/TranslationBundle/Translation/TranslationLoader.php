<?php

namespace Pierstoval\Bundle\TranslationBundle\Translation;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class TranslationLoader
 * Project pierstoval
 *
 * @author Pierstoval
 * @version 1.0 22/03/2014
 */
class TranslationLoader implements LoaderInterface {

    /**
     * @var Pierstoval\Bundle\TranslationBundle\Repository\TranslationRepository
     */
    private $transRepo;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager){
        $this->transRepo = $entityManager->getRepository("PierstovalTranslationBundle:Translation");
//        $this->langRepo = $entityManager->getRepository("PierstovalTranslationBundle:Languages");
    }

    function load($resource, $locale, $domain = 'messages'){

        $translations = $this->transRepo->findBy(array('locale' => $locale, 'domain' => $domain));

        $catalogue = new MessageCatalogue($locale);

        foreach($translations as $translation){
            $catalogue->set($translation->getSource(), $translation->getTranslation(), $domain);
        }

        return $catalogue;
    }

}
