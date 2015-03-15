<?php
/*
* This file is part of the OrbitaleTranslationBundle package.
*
* (c) Alexandre Rock Ancelet <contact@orbitale.io>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Orbitale\Bundle\TranslationBundle\Translation;

use Doctrine\ORM\EntityManager;
use Orbitale\Bundle\TranslationBundle\Entity\Translation;
use Orbitale\Bundle\TranslationBundle\Repository\TranslationRepository;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class TranslationLoader implements LoaderInterface
{

    /**
     * @var TranslationRepository
     */
    private $transRepo;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->transRepo = $entityManager->getRepository("OrbitaleTranslationBundle:Translation");
    }

    function load($resource, $locale, $domain = 'messages')
    {

        /** @var Translation[] $translations */
        $translations = $this->transRepo->findBy(array('locale' => $locale, 'domain' => $domain));

        $catalogue = new MessageCatalogue($locale);

        foreach ($translations as $translation) {
            $catalogue->set($translation->getSource(), $translation->getTranslation(), $domain);
        }

        return $catalogue;
    }

}
