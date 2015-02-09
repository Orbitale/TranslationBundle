<?php
/*
* This file is part of the PierstovalTranslationBundle package.
*
* (c) Alexandre "Pierstoval" Rock Ancelet <pierstoval@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Pierstoval\Bundle\TranslationBundle\Tests\Translator;

use Doctrine\ORM\EntityManager;
use Pierstoval\Bundle\TranslationBundle\Entity\Translation;
use Pierstoval\Bundle\TranslationBundle\Repository\TranslationRepository;
use Pierstoval\Bundle\TranslationBundle\Tests\Fixtures\AbstractTestCase;
use Pierstoval\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;

class TranslatorTest extends AbstractTestCase
{

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->translator = $this->getKernel()->getContainer()->get('pierstoval_translator');
        $this->translator->setFlushStrategy(Translator::FLUSH_RUNTIME);
        $this->translator->setFallbackLocales(array($this->getKernel()->getContainer()->getParameter('locale')));
        $this->em = $this->getKernel()->getContainer()->get('doctrine')->getManager();
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator->emptyCatalogue();

        // Ensure the table is empty
        $connection = $this->em->getConnection();
        $connection->query($connection->getDatabasePlatform()->getTruncateTableSQL('pierstoval_translations', true));

        // And ensure the entityManager is empty of any managed entity
        $this->em->clear();
    }

    public function testManualConstruct()
    {
        $exception = '';
        try {
            $this->bootKernel();
            $translator = new Translator($this->getKernel()->getContainer(), new MessageSelector());
            $translator->__construct($this->getKernel()->getContainer(), new MessageSelector());
        } catch (\Exception $e) {
            $exception = $e->getMessage();
        }
        $this->assertEmpty($exception, $exception);
    }

    public function testLangs()
    {
        $this->assertEquals(array('fr' => 'French'), $this->translator->getLangs());
    }

    /**
     * @dataProvider provideTranslate
     */
    public function testTranslate($source, $expected, $domain = null, $locale = null, $params = array())
    {
        $this->assertEquals($this->translator->trans($source, $params, $domain, $locale), $expected);
    }

    public function provideTranslate()
    {
        return array(
            array('', ''),
            array('0', '0'),
            array('  ', '  '),
            array('Message', 'Message'),
            array('Hello %world%!', 'Hello World!', null, null, array('%world%' => 'World'))
        );
    }

    /**
     * @dataProvider provideTranschoice
     */
    public function testTranschoice($source, $expected, $number)
    {
        $this->assertEquals($this->translator->transChoice($source, $number), $expected);
    }

    public function provideTranschoice()
    {
        return array(
            array('', '', null),
            array('0', '0', null),
            array('  ', '', null),
            array('Message', 'Message', null),
            array('{0} There is no apples', 'There is no apples', 0),
            array('{1} There is one apple', 'There is one apple', 1),
            array('There are %count% apples', 'There are 2 apples', 2),
        );
    }

    /**
     * @dataProvider provideEntities
     */
    public function testEntities($source, $parameters, $locale, $domain, $translation, $expected, $transChoice = false)
    {
        // Force database insertion
        if ($transChoice) {
            $this->translator->transChoice($source, $parameters['%count%'], $parameters, $domain, $locale);
        } else {
            $this->translator->trans($source, $parameters, $domain, $locale);
        }

        $token = $this->generateToken($source, $domain, $locale);

        /** @var TranslationRepository $repo */
        $repo = $this->em->getRepository('PierstovalTranslationBundle:Translation');

        /** @var Translation $translationObject */
        $translationObject = $repo->findOneBy(array('token' => $token));

        $this->assertNotNull($translationObject);
        $this->assertNotNull($translationObject->getId());

        $this->assertEquals(1, $translationObject->getId());
        $this->assertEquals($locale, $translationObject->getLocale());
        $this->assertEquals($domain, $translationObject->getDomain());
        $this->assertEquals($source, $translationObject->getSource());
        $this->assertEquals($token, $translationObject->getToken());
        $this->assertEquals($translationObject->getSource(), $translationObject->__toString());
        $this->assertNull($translationObject->getTranslation());

        $translationObject->setTranslation($translation);
        $this->em->persist($translationObject);
        $this->em->flush();
        $this->em->detach($translationObject);

        // Reload translation from database
        $translationObject = $repo->findOneBy(array('source' => $source));

        // Double-check, for potential corruption
        $this->assertEquals(1, $translationObject->getId());
        $this->assertEquals($locale, $translationObject->getLocale());
        $this->assertEquals($domain, $translationObject->getDomain());
        $this->assertEquals($source, $translationObject->getSource());
        $this->assertEquals($token, $translationObject->getToken());

        if ($transChoice) {
            $this->assertEquals($expected, $this->translator->transChoice($source, $parameters['%count%'], $parameters, $domain, $locale));
        } else {
            $this->assertEquals($expected, $this->translator->trans($source, $parameters, $domain, $locale));
        }
    }

    public function provideEntities()
    {
        return array(
            array('Simple translation', array(), 'fr', 'test_entity', 'Traduction de base', 'Traduction de base'),
            array('Simple translation', array(), 'fr', 'test_entity', 'Traduction de base', 'Traduction de base'),
            array('Simple translation %count%', array('%count%' => 1), 'fr', 'test_entity', 'Traduction de base %count%', 'Traduction de base 1'),
            array('{0} There is no apples', array('%count%' => 0), 'fr', 'test_entity', '{0} Il n\'y a aucune pomme', 'Il n\'y a aucune pomme', true),
            array('{1} There is one apple', array('%count%' => 1), 'fr', 'test_entity', '{1} Il y a une pomme', 'Il y a une pomme', true),
            array(']1,Inf] There are %count% apples', array('%count%' => 2), 'fr', 'test_entity', ']1,Inf] Il y a %count% pommes', 'Il y a 2 pommes', true),
        );
    }

    public function testInsertion()
    {
        $translation = new Translation();
        $translation->setSource('Test 1')
            ->setLocale('fr')
            ->setDomain('messages')
            ->setToken('arbitrary_token')
        ;

        $this->em->persist($translation);
        $this->em->flush();
        $this->em->detach($translation);

        /** @var TranslationRepository $repo */
        $repo = $this->em->getRepository('PierstovalTranslationBundle:Translation');

        $tokens = $repo->findByTokens(array($translation->getToken()));
        $this->assertNotEmpty($tokens);

        /** @var Translation $firstResult */
        $firstResult = current($tokens);

        $this->assertEquals($firstResult->getToken(), $translation->getToken());
    }

    public function testManualInsertion()
    {
        if ($this->translator->getFlushStrategy() !== Translator::FLUSH_NONE) {
            $this->translator->setFlushStrategy(Translator::FLUSH_NONE);
        }

        $this->translator->trans('test', array(), 'messages', 'fr');

        $token = $this->generateToken('test', 'messages', 'fr');

        /** @var TranslationRepository $repo */
        $repo = $this->em->getRepository('PierstovalTranslationBundle:Translation');

        $this->translator->flushTranslations();

        $dbToken = $repo->findOneBy(array('token' => $token));
        $this->assertNotNull($dbToken);

        $this->assertEquals($dbToken->getToken(), $token);

        $dbToken->setTranslation('Translated !');
        $this->em->persist($dbToken);
        $this->em->flush();

        // Empty catalogue to reset it automatically
        $this->translator->emptyCatalogue();

        // Searches back for the new token
        $translation = $this->translator->trans('test', array(), 'messages', 'fr');

        $this->assertEquals('Translated !', $translation);
    }

    public function testGetLocalesInDb()
    {
        $localesToSave = array('fr', 'en', 'de', 'es', 'sv', 'ru');

        /** @var Translation[] $translations */
        $translations = array();
        foreach ($localesToSave as $locale) {
            $translation = new Translation();
            $translation->setSource('Test '.$locale)->setLocale($locale)->setDomain('messages')->setToken('arbitrary_token_'.$locale);
            $translations[] = $translation;
            $this->em->persist($translation);
        }

        $this->em->flush();

        /** @var TranslationRepository $repo */
        $repo = $this->em->getRepository('PierstovalTranslationBundle:Translation');

        $locales = $repo->getLocales();

        foreach ($localesToSave as $locale) {
            $this->assertEquals(true, in_array($locale, $locales));
        }
    }

    public function testDomainsInDb()
    {
        $domainsToSave = array('messages', 'garbage_domain', 'another_creepy_domain', 'hey_what_about', 'FOSUserBundle');

        /** @var Translation[] $translations */
        $translations = array();
        foreach ($domainsToSave as $domain) {
            $translation = new Translation();
            $translation->setSource('Test '.$domain)->setDomain($domain)->setLocale('fr')->setToken('arbitrary_token_'.$domain);
            $translations[] = $translation;
            $this->em->persist($translation);
        }

        $this->em->flush();

        /** @var TranslationRepository $repo */
        $repo = $this->em->getRepository('PierstovalTranslationBundle:Translation');

        $domains = $repo->getDomains();

        foreach ($domainsToSave as $domain) {
            $this->assertEquals(true, in_array($domain, $domains));
        }
    }

    public function testTranslationsLikeOne()
    {
        $translation1 = new Translation();
        $translation1->setSource('Translation likes only one')->setLocale('fr')->setDomain('messages')->setToken('arbitrary_token_fr')->setTranslation('Test traductions similaires unitaires');
        $this->em->persist($translation1);

        $translation2 = new Translation();
        $translation2->setSource('Translation likes only one')->setLocale('en')->setDomain('messages')->setToken('arbitrary_token_en');
        $this->em->persist($translation2);

        $this->em->flush();

        /** @var TranslationRepository $repo */
        $repo = $this->em->getRepository('PierstovalTranslationBundle:Translation');

        $likes = $repo->findOneLikes($translation1);
        $this->assertNotNull($likes);
        $this->assertCount(2, $likes);
        $number = 0;
        foreach ($likes as $like) {
            $number += preg_match('~'.$like->getSource().'~isUu',       $translation1->getSource());
            $number += preg_match('~'.$like->getTranslation().'~isUu',  $translation1->getSource());
            $number += preg_match('~'.$like->getSource().'~isUu',       $translation1->getTranslation());
            $number += preg_match('~'.$like->getTranslation().'~isUu',  $translation1->getTranslation());
        }
        $this->assertGreaterThan(0, $number);
    }

    public function testTranslationsLikeRaw()
    {
        $translation1 = new Translation();
        $translation1->setSource('Translation likes test')->setLocale('fr')->setDomain('messages')->setToken('arbitrary_token_fr')->setTranslation('Test traductions similaires');
        $this->em->persist($translation1);

        $translation2 = new Translation();
        $translation2->setSource('Translation likes test')->setLocale('en')->setDomain('messages')->setToken('arbitrary_token_en');
        $this->em->persist($translation2);

        $this->em->flush();

        /** @var TranslationRepository $repo */
        $repo = $this->em->getRepository('PierstovalTranslationBundle:Translation');

        $likes = $repo->findLikes();
        $this->assertNotEmpty($likes);
        $this->assertCount(2, $likes);
        $translation = $likes[0];
        $firstLikes = $translation->getTranslationsLike();
        $this->assertCount(1, $firstLikes);
        $this->assertEquals($translation2->getId(), $firstLikes[0]->getId());
        foreach ($firstLikes as $like) {
            $translation->removeTranslationLike($like);
        }
        $this->assertEmpty($translation->getTranslationsLike());

    }

    public function testTranslationLikesLocale()
    {
        $translation1 = new Translation();
        $translation1->setSource('Translation likes locale test')->setLocale('fr')->setDomain('messages')->setToken('arbitrary_token_fr')->setTranslation('Test traductions similaires par locale');
        $this->em->persist($translation1);

        $translation2 = new Translation();
        $translation2->setSource('Translation likes locale test')->setLocale('en')->setDomain('messages')->setToken('arbitrary_token_en');
        $this->em->persist($translation2);

        $this->em->flush();

        /** @var TranslationRepository $repo */
        $repo = $this->em->getRepository('PierstovalTranslationBundle:Translation');

        $likesFR = $repo->findLikes('fr');
        $this->assertNotEmpty($likesFR);
        $this->assertCount(1, $likesFR);
        $translation = $likesFR[0];
        $firstLikes = $translation->getTranslationsLike();
        $this->assertCount(1, $firstLikes);
        $this->assertEquals($translation2->getId(), $firstLikes[0]->getId());
        foreach ($firstLikes as $like) {
            $translation->removeTranslationLike($like);
        }
        $this->assertEmpty($translation->getTranslationsLike());
    }

    public function testTranslationsLikeDomain()
    {
        $translation1 = new Translation();
        $translation1->setSource('Translation likes domain test')->setLocale('fr')->setDomain('messages')->setToken('arbitrary_token_fr')->setTranslation('Test traductions similaires par domaine');
        $this->em->persist($translation1);

        $translation2 = new Translation();
        $translation2->setSource('Translation likes domain test')->setLocale('en')->setDomain('messages')->setToken('arbitrary_token_en');
        $this->em->persist($translation2);

        $this->em->flush();

        /** @var TranslationRepository $repo */
        $repo = $this->em->getRepository('PierstovalTranslationBundle:Translation');

        $likesMessages = $repo->findLikes(null, 'messages');
        $this->assertNotEmpty($likesMessages);
        $this->assertCount(2, $likesMessages);
        $translation = $likesMessages[0];
        $firstLikes = $translation->getTranslationsLike();
        $this->assertCount(1, $firstLikes);
        $this->assertEquals($translation2->getId(), $firstLikes[0]->getId());
        foreach ($firstLikes as $like) {
            $translation->removeTranslationLike($like);
        }
        $this->assertEmpty($translation->getTranslationsLike());
    }

    public function testDestructFlush()
    {
        $this->bootKernel();
        $translator = new Translator($this->getKernel()->getContainer(), new MessageSelector());
        $translator->emptyCatalogue();
        $translator->setFlushStrategy(Translator::FLUSH_TERMINATE);

        $translator->trans('test', array(), 'messages', 'fr');
        $token = $this->generateToken('test', 'messages', 'fr');

        $translation = $this->em->getRepository('PierstovalTranslationBundle:Translation')->findOneBy(array('token' => $token));
        $this->assertNull($translation);

        // Triggers the translator's __destruct() method
        unset($translator);

        $translation = $this->em->getRepository('PierstovalTranslationBundle:Translation')->findOneBy(array('token' => $token));
        $this->assertNotNull($translation);
    }

    public function testSomeEmptyValues()
    {
        $this->assertNull($this->translator->findToken('Arbitrary_empty_token'));

        $thisIsEmpty = 'this shall be empty';
        $this->translator->trans($thisIsEmpty);

        // Calling trans() twice to trigger some specific behavior in the translator,
        // which retrieves the id when there is no translation in the object.
        $this->assertEquals($thisIsEmpty, $this->translator->trans($thisIsEmpty));
    }

    public function testSomeExceptions()
    {
        $this->translator->setFallbackLocales(array());

        $exception = false;
        try {
            $this->translator->getTranslation('test with no fallback locale');
        } catch (\Exception $e) {
            $exception = $e->getMessage();
        }
        $this->assertEquals('Could not retrieve any locale from the translator.', $exception);
    }

}
