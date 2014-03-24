<?php

namespace Pierstoval\Bundle\TranslationBundle\Command;

use Pierstoval\Bundle\TranslationBundle\Entity\Languages;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Translation\Catalogue\DiffOperation;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Cette commande crée une langue supplémentaire
 *
 * @author Pierstoval <pierstoval@gmail.com>
 */
class LanguageAddCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pierstoval:translation:language-add')
            ->setDefinition(array(
                new InputArgument('locale', InputArgument::OPTIONAL, 'The locale'),
                new InputArgument('name', InputArgument::OPTIONAL, 'The full language name. Prompted if not specified'),
            ))
            ->setDescription('Adds a new language to the translator manager')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command adds a new language which will be used by the translator.
Using this command is <info>necessary</info> if you want to use PierstovalTranslationBundle properly.

Example :

<info>php %command.full_name% en</info>
<info>php %command.full_name% fr_FR French</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $locale = $input->getArgument('locale');
        $name = $input->getArgument('name');

        $dialog = $this->getHelperSet()->get('dialog');

        if (!$locale) {
            $locale = $dialog->askAndValidate(
                $output,
                'Enter the locale : ',
                function ($answer) {
                    if (!trim($answer)) {
                        throw new \RunTimeException(
                            'You must specify a locale.'
                        );
                    }
                    return $answer;
                }
            );
        }

        if (!$name) {
            $name = $dialog->ask(
                $output,
                'Enter the full language name [<info>'.$locale.'</info>] : ',
                $locale
            );
        }

        $em = $this->getContainer()->get('doctrine')->getManager();

        $repo = $em->getRepository('PierstovalTranslationBundle:Languages');

        $exists = $repo->findOneByLocale($locale);

        if ($exists) {
            throw new \Exception(
                'Locale already exists.'
            );
        }

        $lang = new Languages();

        $lang->setLocale($locale)->setName($name);

        $em->persist($lang);
        $em->flush();

        $output->writeln('Done ! Thanks for using Pierstoval\'s translation tool !');
    }
}
