<?php

namespace App\Form;

use App\Entity\DailyRoutineTask;
use App\Entity\User;
use App\Entity\WellbeingTracker;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WellbeingTrackerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date',
                'attr' => ['class' => 'form-control']
            ])
            ->add('mood', IntegerType::class, [
                'label' => 'Mood (1-5)',
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 5]
            ])
            ->add('stress', IntegerType::class, [
                'label' => 'Stress (1-5)',
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 5]
            ])
            ->add('energy', IntegerType::class, [
                'label' => 'Energy (1-5)',
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 5]
            ])
            ->add('sleepHours', NumberType::class, [
                'label' => 'Sleep Hours',
                'attr' => ['class' => 'form-control', 'step' => '0.5']
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'nom',
                'label' => 'Utilisateur',
                'placeholder' => 'Sélectionner un utilisateur...',
                'required' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('routineTask', EntityType::class, [
                'class' => DailyRoutineTask::class,
                'choice_label' => 'title',
                'label' => 'Tâche de routine liée',
                'placeholder' => 'Aucune tâche liée (optionnel)',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WellbeingTracker::class,
        ]);
    }
}
