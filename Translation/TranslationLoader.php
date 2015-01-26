<?php

namespace Pierstoval\Bundle\TranslationBundle\Translation;

use Pierstoval\Bundle\TranslationBundle\Entity\Translation;
use Pierstoval\Bundle\TranslationBundle\Repository\TranslationRepository;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Class TranslationLoader
 * Project pierstoval
 *
 * @author Pierstoval
 * @version 1.0 22/03/2014
 */
class TranslationLoader implements LoaderInterface {

    /**
     * @var TranslationRepository
     */
    private $transRepo;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager){
        $this->transRepo = $entityManager->getRepository("PierstovalTranslationBundle:Translation");
    }

    function load($resource, $locale, $domain = 'messages'){

        /** @var Translation[] $translations */
        $translations = $this->transRepo->findBy(array('locale' => $locale, 'domain' => $domain));

        $catalogue = new MessageCatalogue($locale);

        foreach($translations as $translation){
            $catalogue->set($translation->getSource(), $translation->getTranslation(), $domain);
        }

        return $catalogue;
    }

}
