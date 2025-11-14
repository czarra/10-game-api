<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\GameTask;
use App\Entity\Task;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GameTaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('task', EntityType::class, [
                'class' => Task::class,
                'choice_label' => 'name',
                'label' => 'Zadanie',
                'placeholder' => 'Wybierz zadanie',
            ])
            ->add('sequenceOrder', NumberType::class, [
                'label' => 'Kolejność',
                'attr' => ['min' => 1],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GameTask::class,
        ]);
    }
}
