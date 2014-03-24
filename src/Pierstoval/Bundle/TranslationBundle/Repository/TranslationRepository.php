<?php
namespace Pierstoval\Bundle\TranslationBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * TranslationRepository
 *
 */
class TranslationRepository extends EntityRepository {

    public function findByTokens($tokens = array()) {
        return $this->findBy(array('token' => $tokens));
    }
}
