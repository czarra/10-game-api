<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Route\RouteCollectionInterface;

final class UserGameAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('game.name', null, ['label' => 'Nazwa Gry'])
            ->add('user.email', null, ['label' => 'Użytkownik'])
            ->add('startedAt', null, [
                'label' => 'Data rozpoczęcia',
                'format' => 'Y-m-d H:i:s'
            ])
            ->add('completedAt', null, [
                'label' => 'Data ukończenia',
                'format' => 'Y-m-d H:i:s'
            ])
            ->add('duration', 'string', [
                'label' => 'Czas trwania',
                'template' => 'admin/list/list_duration.html.twig'
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $datagrid): void
    {
        $datagrid
            ->add('game', null, ['label' => 'Gra'])
            ->add('user', null, ['label' => 'Użytkownik']);
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $rootAlias = $query->getRootAliases()[0];

        $query->andWhere($query->expr()->isNotNull($rootAlias . '.completedAt'));
        
        // Opcjonalne dołączenie, jeśli Sonata nie robi tego automatycznie
        $query->leftJoin($rootAlias . '.game', 'g');
        $query->leftJoin($rootAlias . '.user', 'u');
        return $query;
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
        $collection->remove('edit');
        $collection->remove('delete');
        $collection->remove('export');
    }
}
