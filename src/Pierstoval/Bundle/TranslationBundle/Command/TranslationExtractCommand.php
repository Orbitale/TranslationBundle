<?php

namespace Pierstoval\Bundle\TranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Cette commande extrait les éléments de traduction dans la BDD vers des fichiers utilisés par Symfony2
 *
 * @author Pierstoval
 * @version 1.0 24/03/2014
 */
class TranslationExtractCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pierstoval:translation:extract')
            ->setDefinition(array(
                new InputArgument('locale', InputArgument::REQUIRED, 'The locale'),
                new InputOption(
                    'output-format', 'f', InputOption::VALUE_OPTIONAL,
                    'Override the default output format', 'yml'
                ),
                new InputOption(
                    'output-directory', 'o', InputOption::VALUE_OPTIONAL,
                    'Sets up the output directory <comment>(default : app/Resources/translations/)</comment>'
                ),
                new InputOption(
                    'keep-files', null, InputOption::VALUE_NONE,
                    'By default, all existing files are overwritten. Turn on this option to keep existing files.'
                ),
                new InputOption(
                    'dirty', 'd', InputOption::VALUE_NONE,
                    'Extracts even non-translated elements'
                )
            ))
            ->setDescription('Extracts the database translations into files')
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
        $locale = $input->getArgument('locale');

        $verbosity = $output->getVerbosity();

        $extractor = $this->getContainer()->get('pierstoval.translation.extractor');

        $extractor->cli($input, $output);

        $outputCheck = $extractor->checkOutputDir($outputDirectory, true);

        if (1 < $verbosity) {
            if (preg_match('#_exists$#isUu', $outputCheck)) {
                $method = 'Retrieved';
            } else {
                $method = 'Created';
            }

            $from = preg_replace('#^([^_]+)_.*$#isUu', '$1', $outputCheck);

            $output->writeln('<info>'.$method.'</info> output directory from <info>'.$from.'</info>.');
        }

        if (1 < $verbosity) {
            $output->writeln('Using following output directory : <info>'.$outputDirectory.'</info>');
        }

        // Lancement de la commande du service d'extraction de traductions
        $done = $extractor->extract($locale, $outputFormat, $outputDirectory, $keepFiles, $dirty);

        if ($done) {
            $output->writeln('Done ! Thanks for using Pierstoval\'s translation tool !');
        } else {
            $output->writeln('An unknown error has occurred, please check your configuration and datas.');
        }
    }
}
