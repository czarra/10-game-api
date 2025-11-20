<?php

declare(strict_types=1);

namespace App\Admin;

use App\Form\GameTaskType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Sonata\AdminBundle\Show\ShowMapper;

final class GameAdmin extends AbstractAdmin
{
    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $queryBuilder = $query->getQueryBuilder();
        $rootAlias = current($queryBuilder->getRootAliases());
        $queryBuilder->andWhere(sprintf('%s.deletedAt IS NULL', $rootAlias));

        return $query;
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Informacje o Grze', ['class' => 'col-md-6'])
                ->add('name')
                ->add('description')
                ->add('isAvailable')
            ->end()
            ->with('Zadania w Grze', ['class' => 'col-md-12'])
                ->add('gameTasks', CollectionType::class, [
                    'label' => 'Zadania',
                    'entry_type' => GameTaskType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'prototype' => true,
                    'attr' => [
                        'class' => 'sonata-collection-container',
                    ],
                ])
            ->end();
    }

    protected function configureDatagridFilters(DatagridMapper $datagrid): void
    {
        $datagrid
            ->add('name')
            ->add('isAvailable');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('name')
            ->add('isAvailable')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('name')
            ->add('description')
            ->add('isAvailable');
    }
}
