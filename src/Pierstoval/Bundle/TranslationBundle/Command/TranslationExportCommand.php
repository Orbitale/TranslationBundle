<?php

namespace Pierstoval\Bundle\TranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Translation\Catalogue\DiffOperation;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Cette commande exporte la traduction de la BDD vers des fichiers utilisés par Symfony2
 *
 * @author Pierstoval <pierstoval@gmail.com>
 */
class TranslationExportCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pierstoval:translation:export')
            ->setDefinition(array(
                new InputArgument('locale', InputArgument::REQUIRED, 'The locale'),
                new InputOption(
                    'output-format', 'f', InputOption::VALUE_OPTIONAL,
                    'Override the default output format', 'yml'
                ),
                new InputOption(
                    'output-directory', 'o', InputOption::VALUE_OPTIONAL,
                    'Sets up the output directory <comment>(default : specified in configuration)</comment>'
                ),
                new InputOption(
                    'keep-files', null, InputOption::VALUE_NONE,
                    'By default, all existing files are overwritten. Turn on this option to keep existing files.'
                ),
                new InputOption(
                    'dirty', 'd', InputOption::VALUE_NONE,
                    'Export even non-translated elements'
                )
            ))
            ->setDescription('Exports the database translations into files')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command extracts translation strings from database
and writes files in the configured output directory.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $dirty = $input->getOption('dirty');
        $keepFiles = $input->getOption('keep-files');
        $outputFormat = $input->getOption('output-format');
        $outputDirectory = $input->getOption('output-directory');

        $verbosity = $output->getVerbosity();

        $root_dir = $this->getContainer()->getParameter('kernel.root_dir');

        if (!$outputDirectory) {
            if (2 < $verbosity) {
                $output->writeln('Output directory not specified, using <comment>default directory</comment>.');
            }
            $outputDirectory = $root_dir . '/Resources/translations/';
            if (3 < $verbosity) {
                $output->writeln('Using following output directory : ');
                $output->writeln($outputDirectory);
            }
            if (!is_dir($outputDirectory)) {
                if (2 < $verbosity) {
                    $output->writeln('Output directory does not exist, creating it.');
                }
                mkdir($outputDirectory, 0777, true);
            }
        }

        if (!preg_match('#[/\\\]$#isUu', $outputDirectory)) {
            if (4 < $verbosity) {
                $output->writeln('Fixing output directory trailing (back)slashes.');
            }
            $outputDirectory .= '/';
        }

        $locale = $input->getArgument('locale');

        // check format
        $writer = $this->getContainer()->get('translation.writer');
        $supportedFormats = $writer->getFormats();
        if (!in_array($outputFormat, $supportedFormats)) {
            $output->writeln('<error>Wrong output format</error>');
            $output->writeln('Supported formats are '.implode(', ', $supportedFormats).'.');

            return 1;
        }

        $repo = $this->getContainer()->get('doctrine')->getManager()->getRepository('PierstovalTranslationBundle:Translation');

        $catalogue = new MessageCatalogue($locale);

        if (3 < $verbosity) {
            $output->writeln('Retrieving elements from database');
        }
        $datas = $repo->findBy(array('locale'=>$locale));

        $dirty_elements = 0;
        $existing_files = array();
        $overwritten_files = array();
        $output->writeln('Preparing files...');
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
        if (3 < $verbosity) {
            $output->writeln('Detecting <info>'.count($existing_files).'</info> existing file(s).');
            $output->writeln('Overwriting <info>'.count($overwritten_files).'</info> file(s).');
            $output->writeln('Found <info>'.$dirty_elements.'</info> "dirty" elements (not translated in database).');
        }

        // process catalogues
        $emptyCatalogue = new MessageCatalogue($locale);
        $operation = new MergeOperation($emptyCatalogue, $catalogue);

        // save the files
        $output->writeln('Processing writing...');
        $writer->writeTranslations($operation->getResult(), $input->getOption('output-format'), array('path' => $outputDirectory));

        $cache_dirs = $this->getContainer()->getParameter('kernel.cache_dir').'/../';

        $env_dirs = glob($cache_dirs.'*');

        $output->writeln('Clearing cache catalogues...');
        foreach ($env_dirs as $dir) {
            if (is_dir($dir.'/translations')) {
                $catalogues = glob($dir.'/translations/*');
                foreach ($catalogues as $catalogue) {
                    unlink($catalogue);
                }
            }
        }

        $output->writeln('Done ! Thanks for using Pierstoval\'s translation tool !');
    }
}
