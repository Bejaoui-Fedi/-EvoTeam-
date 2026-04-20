<?php

namespace App\Form;

use App\Entity\Exercise;
use App\Entity\Objective;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExerciseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Exercise Title',
                'attr' => ['placeholder' => 'Enter exercise title...', 'class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['placeholder' => 'Describe the exercise...', 'class' => 'form-control', 'rows' => 3]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Physical' => 'physical',
                    'Meditation' => 'meditation',
                    'Breathing' => 'breathing',
                    'Mental' => 'mental',
                    'Other' => 'other',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('durationMinutes', IntegerType::class, [
                'label' => 'Duration (minutes)',
                'attr' => ['class' => 'form-control', 'min' => 1]
            ])
            ->add('difficulty', ChoiceType::class, [
                'label' => 'Difficulty',
                'choices' => [
                    'Easy' => 'easy',
                    'Medium' => 'medium',
                    'Hard' => 'hard',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('mediaUrl', UrlType::class, [
                'label' => 'Media URL (Image/Video)',
                'required' => false,
                'attr' => ['placeholder' => 'https://...', 'class' => 'form-control']
            ])
            ->add('steps', TextareaType::class, [
                'label' => 'Steps / Instructions',
                'required' => false,
                'attr' => ['placeholder' => '1. Do this\n2. Do that...', 'class' => 'form-control', 'rows' => 5]
            ])
            ->add('objective', EntityType::class, [
                'class' => Objective::class,
                'choice_label' => 'title',
                'label' => 'Associated Objective',
                'attr' => ['class' => 'form-select']
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Publish immediately?',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exercise::class,
        ]);
    }
}
