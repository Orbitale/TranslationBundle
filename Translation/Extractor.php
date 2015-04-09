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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Writer\TranslationWriter;

/**
 * This service allows to extract translation data from database to files
 */
class Extractor
{

    private $em;
    private $root_dir;
    private $cache_dir;
    private $cli = false;// Used to check if service is called from command line
    private $dir_checked = false;

    /**
     * @var OutputInterface
     */
    private $cliOutput;

    /** @var TranslationWriter */
    private $translation_writer;

    function __construct(EntityManager $em, TranslationWriter $translation_writer, $root_dir, $cache_dir, $configured_dir = null) {
        $this->em = $em;
        $this->translation_writer = $translation_writer;
        $this->root_dir = $root_dir;
        $this->cache_dir = $cache_dir;
        $this->configured_dir = $configured_dir;
    }

    /**
     * Specifies that the extractor is used in CLI.
     * It will allow the extractor to log some informations directly in console.
     *
     * @param OutputInterface $output
     */
    public function cli(OutputInterface $output)
    {
        $this->cli = true;
        $this->cliOutput = $output;
    }

    /**
     * Extracts the specified locale in translation files.
     *
     * @param array|string $locales         The locale to be extracted
     * @param string       $outputFormat    The output format. Must follow Symfony's native translation formats.
     * @param string       $outputDirectory The directory where to store the translation files
     * @param bool         $keepFiles       If true, will not erase already existing files.
     * @param bool         $dirty           If true, will extract all ids that do not have a proper translation (null or empty)
     *
     * @return bool True if extraction succeeds, false instead.
     * @throws \Exception
     */
    public function extract($locales, $outputFormat = 'yml', $outputDirectory = '', $keepFiles = false, $dirty = false)
    {
        return $this->doExtract($locales, $outputFormat, $outputDirectory, $keepFiles, $dirty);
    }

    /**
     * @param array|string $locales
     * @param string       $outputFormat
     * @param string       $outputDirectory
     * @param bool         $keepFiles
     * @param bool         $dirty
     *
     * @return bool
     * @throws \Exception
     */
    protected function doExtract($locales, $outputFormat = 'yml', $outputDirectory = '', $keepFiles = false, $dirty = false) {

        if (!$this->dir_checked || !$outputDirectory) {
            $this->checkOutputDir($outputDirectory, false);
        }

        // check format
        $supportedFormats = $this->translation_writer->getFormats();
        if (!in_array($outputFormat, $supportedFormats)) {
            if ($this->cli) {
                throw new \RuntimeException('<error>Wrong output format</error>. Supported formats are '.implode(', ', $supportedFormats).'.');
            } else {
                throw new \Exception('Wrong output format. Supported formats are '.implode(', ', $supportedFormats).'.');
            }
        }

        // Clear translation cache
        $cache_dirs = $this->cache_dir.'/../';

        $env_dirs = glob($cache_dirs.'*');

        if ($this->cli) {
            $this->cliOutput->writeln('Clearing cache catalogues...');
        }
        foreach ($env_dirs as $dir) {
            if (is_dir($dir.'/translations')) {
                $catalogues = glob($dir.'/translations/*');
                foreach ($catalogues as $catalogue) {
                    unlink($catalogue);
                }
            }
        }

        if (is_string($locales)) {
            $locales = array($locales);
        }

        $done = true;

        foreach ($locales as $locale) {
            $this->extractLocale($locale, $outputFormat, $outputDirectory, $keepFiles, $dirty);
        }

        // Reset des paramètres pour permettre une autre utilisation de l'extracteur
        $this->dir_checked = false;
        $this->cli = false;
        $this->cliOutput = null;

        return $done;
    }

    /**
     * @param string $locale
     * @param string $outputFormat
     * @param string $outputDirectory
     * @param bool   $keepFiles
     * @param bool   $dirty
     */
    protected function extractLocale($locale, $outputFormat = 'yml', $outputDirectory = '', $keepFiles = false, $dirty = false)
    {
        /** @var TranslationRepository $repo */
        $repo = $this->em->getRepository('OrbitaleTranslationBundle:Translation');

        if ($this->cli) {
            $output = $this->cliOutput;
            $verbosity = $output->getVerbosity();
        } else {
            $output = null;
            $verbosity = null;
        }

        $catalogue = new MessageCatalogue($locale);

        if ($this->cli && 1 < $verbosity) {
            $output->writeln('Retrieving elements from database...');
        }

        /** @var Translation[] $datas */
        $datas = $repo->findBy(array('locale' => $locale));

        $dirty_elements = 0;
        $existing_files = array();
        $overwritten_files = array();
        if ($this->cli) {
            $output->writeln('Preparing files...');
        }
        foreach ($datas as $translation) {
            $domain = $translation->getDomain();
            $outputFileName = $outputDirectory.$domain.'.'.$locale.'.'.$outputFormat;
            if (file_exists($outputFileName)) {
                $existing_files[$outputFileName] = $outputFileName;
            }
            if (
                (!$keepFiles || ($keepFiles && !file_exists($outputFileName)))
                &&
                ($dirty || (!$dirty && $translation->getTranslation()))
            ) {
                if (isset($existing_files[$outputFileName])) {
                    $overwritten_files[$outputFileName] = $outputFileName;
                }
                $catalogue->add(array($translation->getSource() => $translation->getTranslation()), $domain);
            }
            if (!$translation->getTranslation()) {
                $dirty_elements++;
            }
        }
        if ($this->cli && 1 < $verbosity) {
            $existing_files = count($existing_files);
            $overwritten_files = count($overwritten_files);
            $output->writeln('    <info>'.$existing_files.'</info> existing file'.($existing_files > 1 ? 's' : '').'.');
            $output->writeln('    <info>'.$overwritten_files.'</info> file'.($overwritten_files > 1 ? 's' : '').' to overwrite.');
            $output->writeln('    <info>'.$dirty_elements.'</info> "dirty" element'.($dirty_elements > 1 ? 's' : '').' '.($dirty_elements ? '<comment>(Source found, but no translation)</comment>.' : ''));
        }

        // process catalogues
        $emptyCatalogue = new MessageCatalogue($locale);
        $operation = new MergeOperation($emptyCatalogue, $catalogue);

        // save the files
        if ($this->cli) {
            $output->writeln(sprintf('Processing extraction in <info>%s</info>', $outputDirectory));
        }

        $mergeOperationResult = $operation->getResult();

        if (method_exists($this->translation_writer, 'disableBackup')) {
            $this->translation_writer->disableBackup();
        }
        $this->translation_writer->writeTranslations($mergeOperationResult, $outputFormat, array('path' => $outputDirectory));
    }

    /**
     * Will check if the output directory exists, and create it recursively if not.
     * As $outputDirectory is passed as reference, it will fix any trimming directory separator on it.
     *
     * @param string $outputDirectory
     * @param bool $return_info
     *
     * @return $this|string
     */
    public function checkOutputDir(&$outputDirectory, $return_info = false)
    {
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
                $outputDirectory = $root_dir.'/Resources/translations/';
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
