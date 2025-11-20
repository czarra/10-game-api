<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\GameTask;
use App\Entity\Task;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

final class GameTaskAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('task', ModelType::class, [
                'class' => Task::class,
                'property' => 'name', // Assuming Task has a 'name' property
                'btn_add' => false, // Disables the add button
                'placeholder' => 'Select a Task',
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) {
                    return $er->createQueryBuilder('o')
                        ->andWhere('o.deletedAt IS NULL');
                },
            ])
            ->add('sequenceOrder', IntegerType::class);
    }

    protected function configureDatagridFilters(DatagridMapper $datagrid): void
    {
        $datagrid
            ->add('task.name'); // Filter by task name
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('task.name')
            ->add('sequenceOrder');
    }
}
