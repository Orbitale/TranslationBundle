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
use Pierstoval\Bundle\TranslationBundle\Tests\Fixtures\AbstractTestCase;
use Pierstoval\Bundle\TranslationBundle\Command\TranslationExtractCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ExtractionTest extends AbstractTestCase {

    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->em = $this->getKernel()->getContainer()->get('doctrine')->getManager();
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        // Ensure the table is empty
        $connection = $this->em->getConnection();
        $connection->query($connection->getDatabasePlatform()->getTruncateTableSQL('pierstoval_translations', true));

        // And ensure the entityManager is empty of any managed entity
        $this->em->clear();
    }

    /**
     * @return Translation[]
     */
    private function processDummyDatas(){
        $translationsToAdd = array(
            array('first.element', 'Premier élément', 'fr', 'messages'),
            array('first.element', 'First Element', 'en', 'messages'),
            array('dirty.translation', null, 'fr', 'messages'),
            array('another.domain', 'Un autre domaine', 'fr', 'other_domain'),
        );
        $finalTranslations = array();
        foreach ($translationsToAdd as $trans) {
            $obj = new Translation();
            $obj
                ->setSource($trans[0])
                ->setTranslation($trans[1])
                ->setLocale($trans[2])
                ->setDomain($trans[3])
                ->setToken($this->generateToken($trans[0], $trans[3], $trans[2]));
            $this->em->persist($obj);
            $finalTranslations[$obj->getToken()] = $obj;
        }
        $this->em->flush();
        return $finalTranslations;
    }

    public function testSuccessfulExtractionCommand()
    {
        $dummyDatas = $this->processDummyDatas();

        $outputDirectory = $this->getKernel()->getContainer()->getParameter('kernel.root_dir').'/../../../vendor/_translations/';

        // Clean the translation output
        foreach (glob($outputDirectory.'/*') as $file) {
            unlink($file);
        }
        if (is_dir($outputDirectory)) {
            rmdir($outputDirectory);
        }

        $command = new TranslationExtractCommand();
        $command->setContainer($this->getKernel()->getContainer());

        // Run with FR locale
        $arrayInput = new ArrayInput(array(
            'locale' => 'fr',
            '--output-directory' => $outputDirectory,
            '--dirty' => true,
        ));
        $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $command->run($arrayInput, $output);

        $shouldBeThereFiles = array(
            'messages' => $outputDirectory.'messages.fr.yml',
            'other_domain' => $outputDirectory.'other_domain.fr.yml',
        );
        foreach ($shouldBeThereFiles as $domain => $file) {
            $this->assertFileExists($file);
            if (file_exists($file)) {
                $translations = Yaml::parse(file_get_contents($file));
                foreach ($translations as $id => $translation) {
                    $token = $this->generateToken($id, $domain, 'fr');
                    $transInDatas = isset($dummyDatas[$token]) ? $dummyDatas[$token]->getTranslation() : null;
                    $this->assertArrayHasKey($token, $dummyDatas);
                    $this->assertEquals($transInDatas, $translation);
                }
            }
        }

        // Save a new translation in database
        // In order to test that the "messages.yml" file is not overwritten
        $notExtractedTranslation = new Translation();
        $notExtractedTranslation->setSource('must_never_be_in_file')->setDomain('messages')->setLocale('fr')->setToken('arbitrary_token');
        $this->em->persist($notExtractedTranslation);
        $this->em->flush();
        $this->em->clear();

        // Run with FR locale
        $arrayInput = new ArrayInput(array(
            'locale' => 'fr',
            '--keep-files' => true,
            '--output-directory' => $outputDirectory,
        ));
        $command->run($arrayInput, new NullOutput());
        $messages = Yaml::parse(file_get_contents($outputDirectory.'messages.fr.yml'));
        $this->assertArrayNotHasKey('must_never_be_in_file', $messages);

        // Save a new translation in database
        // In order to test that the "messages.yml" file IS overwritten
        $newTranslation = new Translation();
        $newTranslation->setSource('must_be_in_file')->setDomain('messages')->setLocale('fr')->setToken('other_arbitrary_token');
        $this->em->persist($newTranslation);
        $this->em->flush();
        $this->em->clear();

        // Run with FR locale
        $arrayInput = new ArrayInput(array(
            'locale' => 'fr',
            '--output-directory' => $outputDirectory,
            '--dirty' => true,
        ));
        $command->run($arrayInput, new NullOutput());
        $messages = Yaml::parse(file_get_contents($outputDirectory.'messages.fr.yml'));
        // This checks that the translation is saved and dirty
        $this->assertNull(array_key_exists('must_be_in_file', $messages) ? $messages['must_be_in_file'] : 'key that must be in file');


        // Test a wrong not supported format in order to check the exception
        $arrayInput = new ArrayInput(array(
            'locale' => 'fr',
            '--output-format' => 'this_format_does_not_exist',
            '--output-directory' => $outputDirectory,
            '--dirty' => true,
        ));
        $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $commandOutput = $command->run($arrayInput, $output);
        $output = $output->fetch();
        $this->assertGreaterThan(0, $commandOutput);
        $this->assertContains('Wrong output format', $output);
        $this->assertContains('An unknown error has occurred, please check your configuration and datas.', $output);

    }

}
