<?php
/*
* This file is part of the OrbitaleTranslationBundle package.
*
* (c) Alexandre Rock Ancelet <contact@orbitale.io>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Orbitale\Bundle\TranslationBundle\Doctrine;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use PDO;
use Orbitale\Bundle\TranslationBundle\Entity\Translation;

class TranslationLikeHydrator extends AbstractHydrator {

    /**
     * @var array
     */
    protected $initializedCollections;

    function hydrateAllData() {
        $results = array();

        $owners = $this->_rsm->columnOwnerMap;
        $mapping_fields = $this->_rsm->fieldMappings;

        // Looping all the rows
        while ($row = $this->_stmt->fetch(PDO::FETCH_ASSOC)) {
            $result = new Translation();
            $like = new Translation();
            foreach ($owners as $dqlAlias => $element) {
                // Uses reflection system to hydrate the object
                $value = $row[$dqlAlias];
                $fieldName = $mapping_fields[$dqlAlias];

                if ( $fieldName !== 'id' || ($fieldName == 'id' && $value) ) {

                    if ($element === 'translationsLike') {
                        // Hydrates a translation-like
                        $refObject   = new \ReflectionObject( $like );
                        $refProperty = $refObject->getProperty( $fieldName );
                        $refProperty->setAccessible(true);
                        $refProperty->setValue($like, $value);
                    } else {
                        // Hydrates a "master" translation
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

        return array_values($results);
    }
}
