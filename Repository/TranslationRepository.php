<?php
namespace Pierstoval\Bundle\TranslationBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Pierstoval\Bundle\TranslationBundle\Entity\Translation;

/**
 * TranslationRepository
 */
class TranslationRepository extends EntityRepository {

    /**
     * Get all translations with mentioned tokens
     * @param array $tokens
     * @return array
     */
    public function findByTokens($tokens = array()) {
        return $this->findBy(array('token' => $tokens));
    }

    /**
     * Get all domains wich have translations in the database
     * @return array
     */
    public function getDomains()
    {
        $result = $this->_em->createQueryBuilder()
            ->select('o.domain')
            ->from($this->_entityName, 'o')
            ->groupBy('o.domain')
            ->getQuery()
            ->getResult()
        ;

        foreach ($result as $k => $v) {
            $result[$k] = $v['domain'];
        }

        return $result;
    }

    /**
     * Get all locales which have translations in the database
     * @return array
     */
    public function getLocales()
    {
        $result = $this->_em->createQueryBuilder()
            ->select('o.locale')
            ->from($this->_entityName, 'o')
            ->groupBy('o.locale')
            ->getQuery()
            ->getResult()
        ;

        foreach ($result as $k => $v) {
            $result[$k] = $v['locale'];
        }

        return $result;
    }

    /**
     * Get all translations which looks like specified translation.
     * This is used to help translating an element by providing translations that are similar but maybe in other locales.
     *
     * @param Translation $translation
     * @return array
     */
    public function findOneLikes(Translation $translation)
    {
        $em = $this->_em;
        $dql = "
        SELECT translationsLike
        FROM PierstovalTranslationBundle:Translation translationsLike
        WHERE
               translationsLike.source LIKE concat('%',:source,'%')
            OR translationsLike.translation LIKE concat('%',:source,'%')
        ";

        $params = array('source' => $translation->getSource());

        if ($translation->getTranslation()) {
            $dql .= "
            OR translationsLike.source LIKE concat('%',:trans,'%')
            OR translationsLike.translation LIKE concat('%',:trans,'%')
            ";
            $params['trans'] = $translation->getTranslation();
        }

        $dql .= " ORDER BY translationsLike.domain ASC, translationsLike.source ASC";

        $query = $em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }

    /**
     * Get all translations with specified parameters (locale and/or domain)
     * and adds all translations that looks like each one in a public "translationsLike" attribute.
     * Uses a special hydrator to fetch all translations like in an array.
     *
     * @param null|string $locale
     * @param null|string $domain
     * @return Translation[]
     * @see Pierstoval\Bundle\TranslationBundle\Doctrine\TranslationLikeHydrator
     */
    public function findLikes($locale = null, $domain = null) {

        $em = $this->_em;
        $dql = "
        SELECT t1, translationsLike
            FROM PierstovalTranslationBundle:Translation t1
            LEFT JOIN PierstovalTranslationBundle:Translation translationsLike
                WITH
                    (
                        t1.source LIKE concat('%',translationsLike.source,'%')
                        OR t1.source LIKE concat('%',translationsLike.translation,'%')
                    ) and (
                        t1.locale != translationsLike.locale
                        OR (
                            t1.locale = translationsLike.locale
                            AND t1.source != translationsLike.source
                        )
                    )
        ";

        $params = array();

        if ($locale || $domain) {
            $dql .= " WHERE ";
            if ($locale) {
                $dql .= ' t1.locale = :locale ';
                $params[':locale'] = $locale;
            }
            if ($domain) {
                if ($locale) { $dql .= " AND "; }
                $dql .= " t1.domain = :domain ";
                $params[':domain'] = $domain;
            }
        }

        $dql .= "
            ORDER BY t1.domain ASC, t1.source ASC
        ";

        $config = $em->getConfiguration();
        $config->addCustomHydrationMode('translation_like', 'Pierstoval\Bundle\TranslationBundle\Doctrine\TranslationLikeHydrator');

        $query = $em->createQuery($dql);

        $query->setParameters($params);

        return $query->getResult('translation_like');
    }

    /**
     * @return Translation[]
     */
    public function getForAdmin()
    {
        $list = $this->findAll();

        $translations = array();
        foreach ($list as $translation) {
            $locale = $translation->getLocale();
            $domain = $translation->getDomain();

            if (!isset($translations[$locale][$domain])) {
                $translations[$locale][$domain] = array(
                    'count' => 0,
                    'total' => 0,
                );
            }
            if ($translation->getTranslation()) {
                $translations[$locale][$domain]['count']++;
            }

            $translations[$locale][$domain]['total']++;
            ksort($translations[$locale]);
        }

        ksort($translations);

        return $translations;
    }
}
