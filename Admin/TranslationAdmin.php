<?php

namespace Pierstoval\Bundle\TranslationBundle\Admin;

use Doctrine\ORM\EntityManager;
use Pierstoval\Bundle\TranslationBundle\Entity\Translation;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;

class TranslationAdmin extends Admin {

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(array('list','edit'));
    }

    protected function configureFormFields(FormMapper $formMapper) {

        $subject = $this->getSubject();

        /** @var EntityManager $em */

        $likes = $this->modelManager->getEntityManager($subject)->getRepository(get_class($subject))->findOneLikes($subject);

        $help = $this->getConfigurationPool()->getContainer()->get('templating')->render('PierstovalTranslationBundle:Translation:sonata_translations_like_help.html.twig', array('translations' => $likes));

        $formMapper
            ->add('locale', 'text', array('disabled'=>true))
            ->add('domain', 'text', array('disabled'=>true))
            ->add('source', 'text', array('disabled'=>true))
            ->add('translation', 'text', array('required'=>false, 'sonata_help' => $help))
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {

        $domains = $this->getConfigurationPool()->getContainer()->get('doctrine')->getRepository(Translation::class)->getDomains();
        $domains = array_combine($domains, $domains);

        $locales = $this->getConfigurationPool()->getContainer()->get('doctrine')->getRepository(Translation::class)->getLocales();
        $locales = array_combine($locales, $locales);

        $datagridMapper
            ->add('source')
            ->add('translation')
            ->add('domain', 'doctrine_orm_string', array('field_type' => 'choice'), null, array('choices' => $domains))
            ->add('locale', 'doctrine_orm_string', array('field_type' => 'choice'), null, array('choices' => $locales))
            ->add('translation_empty', 'doctrine_orm_callback', array(
                'callback' => function(ProxyQuery $queryBuilder, $alias, $field, $value) {
                    if (!$value['value']) {
                        return;
                    }
                    $queryBuilder->andWhere($alias.'.translation IS NULL');
                },
                'field_type' => 'checkbox'
            ))
        ;
    }

    protected function configureListFields(ListMapper $listMapper) {
        $listMapper
            ->add('locale', 'text')
            ->addIdentifier('source', 'text')
            ->addIdentifier('translation', 'text')
            ->add('domain', 'text')
        ;

    }

}
