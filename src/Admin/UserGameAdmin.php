<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
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
            ->add('durationSeconds', 'string', [
                'label' => 'Czas trwania',
                'template' => 'admin/list/list_duration.html.twig',
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
        /** @var ProxyQuery $query */
        $rootAlias = $query->getRootAliases()[0];

        $query
            ->addSelect("TIMESTAMPDIFF(SECOND, {$rootAlias}.startedAt, {$rootAlias}.completedAt) as durationSeconds")
            ->andWhere($query->expr()->isNotNull($rootAlias . '.completedAt'))
            ->leftJoin($rootAlias . '.game', 'g')
            ->leftJoin($rootAlias . '.user', 'u')
            ->orderBy('durationSeconds', 'ASC');

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
