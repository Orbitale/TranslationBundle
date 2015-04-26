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
use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\TranslatorInterface;

class Translator extends BaseTranslator implements TranslatorInterface
{

    /**
     * The translator will flush any new translation directly in the database.
     * It can be good for testing but it may considerably reduce performances.
     */
    const FLUSH_RUNTIME = 0;

    /**
     * Optimal behavior :
     * The translator will only flush translations when the __destruct()
     * or flushTranslations() method are called.
     */
    const FLUSH_TERMINATE = 1;

    /**
     * The translator will never flush the translations automatically,
     * unless the flushTranslations() method is called manually.
     */
    const FLUSH_NONE = 2;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Translation[]
     */
    protected $translationsToPersist = array();

    /**
     * @var string
     */
    protected $locale;

    /**
     * Contains all translations which are recovered from database
     * This attribute is static because it will force the class to always
     * have the same catalogue through the all application, to avoid too many
     * database queries to retrieve the translations.
     * @var array
     */
    protected static $catalogue = array();

    /**
     * This array contains all Translation objects tokens that has been generated during the request
     * The keys are the tokens, and the values are a boolean that determines whether the object is
     * translated or not.
     * @var array|boolean[]
     */
    protected static $requestedTokens = array();

    /**
     * Override the native message selector to be able to use it for `transchoice` method
     * @var MessageSelector
     */
    private $selector;

    /**
     * @var bool
     */
    protected $hasToBeFlushed = false;

    /**
     * @var bool
     */
    protected $flushed = false;

    /**
     * @var EntityManager $_em
     */
    protected $_em;

    /**
     * @var int
     */
    protected $flushStrategy = self::FLUSH_TERMINATE;

    /**
     * @param ContainerInterface $container
     * @param MessageSelector    $selector
     * @param array              $loaderIds
     * @param array              $options
     */
    function __construct($container, MessageSelector $selector, $loaderIds = array(), array $options = array())
    {
        parent::__construct($container, $selector, $loaderIds, $options);

        $this->selector = $selector ?: new MessageSelector();

        $this->_em = $this->container->get('doctrine')->getManager();
    }

    /**
     * @param integer $instantFlush
     * @return $this
     */
    public function setFlushStrategy($instantFlush = self::FLUSH_TERMINATE)
    {
        $this->flushStrategy = $instantFlush;

        return $this;
    }

    /**
     * @return int
     */
    public function getFlushStrategy()
    {
        return $this->flushStrategy;
    }

    /**
     * Returns an array with locales.
     * Keys = locales
     * Values = public languages names
     * @return array
     */
    public function getLangs()
    {
        return $this->container->getParameter('orbitale_translation.locales');
    }

    /**
     * @return $this
     */
    public function emptyCatalogue()
    {
        self::$catalogue = array();

        return $this;
    }

    /**
     * Persists all translations in the $translationsToPersist attribute,
     * then flushes the manager and clears all translations to be persisted
     * @return $this
     */
    public function flushTranslations()
    {
        if ($this->hasToBeFlushed && !$this->flushed) {
            foreach ($this->translationsToPersist as $translation) {
                $this->_em->persist($translation);
            }
            $this->_em->flush();
            $this->_em->clear();
            $this->translationsToPersist = array();
            $this->flushed = true;
        }

        return $this;
    }

    /**
     * @return array|boolean[]
     */
    public function getRequestedTokens()
    {
        return static::$requestedTokens;
    }

    /**
     * In case of, flush is launched anytime the object is destructed.
     * This allows flushing even when there is any kind of error, or when the listener is not triggered.
     */
    public function __destruct()
    {
        if ($this->flushStrategy === self::FLUSH_TERMINATE) {
            $this->flushTranslations();
        }
    }

    /**
     * Searches in native Symfony translation system if a translations exists for given source
     *
     * @param string $locale
     * @param string $source
     * @param string $domain
     * @return string|null
     */
    public function findInNativeCatalogue($locale, $source, $domain)
    {
        if (!isset($this->catalogues[$locale])) {
            // Loads native catalogue
            $this->loadCatalogue($locale);
        }

        return $this->catalogues[$locale]->has($source, $domain)
            && trim($this->catalogues[$locale]->get($source, $domain))
             ? $this->catalogues[$locale]->get($source, $domain)
             : null;
    }

    /**
     * {@inheritdoc}
     */
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        $translation = $this->getTranslation($id, $domain, $locale);

        return strtr($translation, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null)
    {

        if (!isset($parameters['%count%'])) {
            $parameters['%count%'] = $number;
        }

        $translation = $this->getTranslation($id, $domain, $locale);

        return strtr($this->selector->choose($translation, (int)$number, $locale), $parameters);
    }

    /**
     * Finds a translation, first, in the native catalogue.
     * Then, searches for it in the database.
     * If the element is still not found, it will persist a new "dirty" Translation object in the database.
     *
     * @param mixed  $id
     * @param string $domain
     * @param string $locale
     *
     * @return string
     * @throws \Exception
     */
    public function getTranslation($id, $domain = null, $locale = null)
    {
        if (
            !$id
            || (is_string($id) && !trim($id))
            || is_numeric($id)
            || (is_object($id) && method_exists($id, '__toString') && !trim($id->__toString()))
        ) {
            // Avoid translating empty things
            return $id;
        }

        if (null === $locale) {
            $locale = $this->getLocale();
            if (null === $locale && !count($this->getFallbackLocales())) {
                throw new \Exception('Could not retrieve any locale from the translator.');
            } else {
                $fallbackLocales = $this->getFallbackLocales();
                $locale = $fallbackLocales[0];
            }
        } else {
            $this->assertValidLocale($locale);
        }
        if (!$domain) {
            $domain = 'messages';
        }

        // Récupère la traduction dans le catalogue de Symfony2 natif
        $translation = $this->findInNativeCatalogue($locale, $id, $domain);

        if (null === $translation) {

            // Génère le catalogue BDD à partir de la locale et du domaine
            $this->loadDbCatalogue($locale, $domain);

            $token = md5($id.'_'.$domain.'_'.$locale);

            if (!isset(static::$requestedTokens[$token])) {
                static::$requestedTokens[$token] = false;
            }

            /** @var Translation $translation */
            $translation = $this->findToken($token);

            if ($translation) {
                if ($translation->getTranslation()) {
                    static::$requestedTokens[$token] = true;
                    $translation = $translation->getTranslation();
                } else {
                    $translation = $id;
                }
            } else {
                $translation = new Translation();
                $translation
                    ->setToken($token)
                    ->setSource($id)
                    ->setDomain($domain)
                    ->setLocale($locale);
                if ($this->flushStrategy === self::FLUSH_TERMINATE || $this->flushStrategy === self::FLUSH_NONE) {
                    $this->hasToBeFlushed = true;
                    $this->translationsToPersist[] = $translation;
                } elseif ($this->flushStrategy === self::FLUSH_RUNTIME) {
                    $this->_em->persist($translation);
                    $this->_em->flush($translation);
                }
                self::$catalogue[$locale][$domain][$token] = $translation;
                $translation = $id;
            }
        }

        return $translation;
    }

    /**
     * Searches for a token in the static catalogue and returns it if found.
     * @param string $token
     * @return null|Translation
     */
    public function findToken($token)
    {
        $catalogue = self::$catalogue;
        $translation = null;
        foreach ($catalogue as $locale_catalogue) {
            foreach ($locale_catalogue as $domain_catalogue) {
                if (isset($domain_catalogue[$token])) {
                    $translation = $domain_catalogue[$token];
                    break;
                }
            }
        }

        return $translation;
    }

    /**
     * Loads and populates a catalogue from the database.
     * @param string $locale
     * @param string $domain
     */
    protected function loadDbCatalogue($locale, $domain)
    {
        $catalogue = self::$catalogue;

        if (!isset($catalogue[$locale][$domain])) {
            $translations = $this->_em
                ->getRepository('OrbitaleTranslationBundle:Translation')
                ->findBy(array('locale' => $locale, 'domain' => $domain));

            if ($translations) {
                foreach ($translations as $translation) {
                    /** @var Translation $translation */
                    self::$catalogue[$locale][$translation->getDomain()][$translation->getToken()] = $translation;
                }
            }
        }
    }

}
