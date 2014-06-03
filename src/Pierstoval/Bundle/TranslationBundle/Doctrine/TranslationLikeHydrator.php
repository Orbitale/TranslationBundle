<?php

namespace Pierstoval\Bundle\TranslationBundle\Doctrine;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use PDO;
use Pierstoval\Bundle\TranslationBundle\Entity\Translation;

class TranslationLikeHydrator extends AbstractHydrator {

    /**
     * @var array
     */
    protected $initializedCollections;

    function hydrateAllData() {
        $results = array();

        $owners = $this->_rsm->columnOwnerMap;
        $mapping_fields = $this->_rsm->fieldMappings;

        while ($row = $this->_stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($results[$row['id0']])) {
                $result = $results[$row['id0']];
            } else {
                $result = new Translation();
            }
            $like = new Translation();
            foreach ($owners as $dqlAlias => $element) {
                $value = $row[$dqlAlias];
                $fieldName = $mapping_fields[$dqlAlias];

                if ( $fieldName !== 'id' || ($fieldName == 'id' && $value) ) {

                    if ($element == 'translationsLike') {
                        // Add to translation
                        $refObject   = new \ReflectionObject( $like );
                        $refProperty = $refObject->getProperty( $fieldName );
                        $refProperty->setAccessible(true);
                        $refProperty->setValue($like, $value);
                    } else {
                        $refObject   = new \ReflectionObject( $result );
                        $refProperty = $refObject->getProperty( $fieldName );
                        $refProperty->setAccessible(true);
                        $refProperty->setValue($result, $value);
                    }
                }
            }
            if ($result->getId()) {
                if ($like->getId()) {
                    $result->addTranslationLike($like);
                }
                $results[$result->getId()] = $result;
            }
        }

        return $results;
    }
}