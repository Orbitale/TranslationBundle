<?php
namespace Pierstoval\Bundle\TranslationBundle\Translation;


use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Service permettant l'extraction des données de traduction dans la BDD vers des fichiers utilisés par Symfony2
 *
 * @author Pierstoval
 * @version 1.0 24/03/2014
 */
class Extractor {

    private $em;
    private $root_dir;
    private $cache_dir;
    private $cli = false;// Used to check if service is called from command line
    private $dir_checked = false;
    private $cli_input;
    private $cli_output;

    function __construct(EntityManager $em, $translation_writer, $root_dir, $cache_dir, $configured_dir = null) {
        $this->em = $em;
        $this->translation_writer = $translation_writer;
        $this->root_dir = $root_dir;
        $this->cache_dir = $cache_dir;
        $this->configured_dir = $configured_dir;
    }

    public function cli(InputInterface $input, OutputInterface $output) {
        $this->cli = true;
        $this->cli_input = $input;
        $this->cli_output = $output;
    }

    public function extract($locale, $outputFormat = 'yml', $outputDirectory = '', $keepFiles = false, $dirty = false) {

        if (!$this->dir_checked || !$outputDirectory) {
            $this->checkOutputDir($outputDirectory, false);
        }

        $cli = $this->cli;
        if ($cli) {
            $input = $this->cli_input;
            $output = $this->cli_output;
            $verbosity = $output->getVerbosity();
        }

        // check format
        $writer = $this->translation_writer;
        $supportedFormats = $writer->getFormats();
        if (!in_array($outputFormat, $supportedFormats)) {
            if ($cli) {
                throw new \RuntimeException('<error>Wrong output format</error>. Supported formats are '.implode(', ', $supportedFormats).'.');
            } else {
                throw new \Exception('Wrong output format. Supported formats are '.implode(', ', $supportedFormats).'.');
            }
            return false;
        }

        $repo = $this->em->getRepository('PierstovalTranslationBundle:Translation');

        $catalogue = new MessageCatalogue($locale);

        if ($cli && 2 < $verbosity) { $output->writeln('Retrieving elements from database...');}

        $datas = $repo->findBy(array('locale'=>$locale));

        $dirty_elements = 0;
        $existing_files = array();
        $overwritten_files = array();
        if ($cli) { $output->writeln('Preparing files...'); }
        foreach ($datas as $translation) {
            $domain = $translation->getDomain();
            $outputFileName = $outputDirectory.$domain.'.'.$locale.'.'.$outputFormat;
            if (file_exists($outputFileName)) { $existing_files[$outputFileName] = $outputFileName; }
            if (
                ( !$keepFiles || ($keepFiles && !file_exists($outputFileName)) )
                &&
                ( $dirty || (!$dirty && $translation->getTranslation()) )
            ) {
                if (isset($existing_files[$outputFileName])) {
                    $overwritten_files[$outputFileName] = $outputFileName;
                }
                $catalogue->add(array($translation->getSource() => $translation->getTranslation()), $domain);
            }
            if (!$translation->getTranslation()) { $dirty_elements ++; }
        }
        if ($cli && 1 < $verbosity) {
            $existing_files = count($existing_files);
            $overwritten_files = count($overwritten_files);
            $output->writeln("\t".'<info>'.$existing_files.'</info> existing file'.($existing_files>1?'s':'').'.');
            $output->writeln("\t".'<info>'.$overwritten_files.'</info> file'.($overwritten_files>1?'s':'').' to overwrite.');
            $output->writeln("\t".'<info>'.$dirty_elements.'</info> "dirty" element'.($dirty_elements>1?'s':'').') '.($dirty_elements?'<comment>(Source found, but no translation)</comment>.':''));
        }

        // process catalogues
        $emptyCatalogue = new MessageCatalogue($locale);
        $operation = new MergeOperation($emptyCatalogue, $catalogue);

        // save the files
        if ($cli) {
            $output->writeln('Processing extraction...');
        }
        $writer->writeTranslations($operation->getResult(), $outputFormat, array('path' => $outputDirectory));

        $cache_dirs = $this->cache_dir.'/../';

        $env_dirs = glob($cache_dirs.'*');

        if ($cli) { $output->writeln('Clearing cache catalogues...'); }
        foreach ($env_dirs as $dir) {
            if (is_dir($dir.'/translations')) {
                $catalogues = glob($dir.'/translations/*');
                foreach ($catalogues as $catalogue) {
                    unlink($catalogue);
                }
            }
        }

        // Reset des paramètres pour permettre une autre utilisation de l'extracteur
        $this->dir_checked = false;
        $this->cli = false;
        $this->cli_output = null;
        $this->cli_input = null;

        return true;
    }

    public function checkOutputDir(&$outputDirectory, $return_info = false) {
        $config_param = $this->configured_dir;

        if ($config_param) {
            $outputDirectory = $config_param;
            $info_from = 'configuration';
            if (is_dir($outputDirectory)) {
                $info_method = 'exists';
            } else {
                mkdir($outputDirectory, 0775, true);
                $info_method = 'created';
            }
        } else {
            if (!$outputDirectory) {
                $root_dir = $this->root_dir;
                $outputDirectory = $root_dir . '/Resources/translations/';
                $info_from = 'default';
                if (is_dir($outputDirectory)) {
                    $info_method = 'exists';
                } else {
                    mkdir($outputDirectory, 0775, true);
                    $info_method = 'created';
                }
            } else {
                $info_from = 'argument';
                if (is_dir($outputDirectory)) {
                    $info_method = 'exists';
                } else {
                    mkdir($outputDirectory, 0775, true);
                    $info_method = 'created';
                }
            }
        }

        if (!preg_match('#[/\\\]$#isUu', $outputDirectory)) {
            $outputDirectory .= '/';
        }

        $this->dir_checked = true;

        if ($return_info) {
            return $info_from.'_'.$info_method;
        } else {
            return $this;
        }
    }
} 