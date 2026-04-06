<?php

namespace App\Form;

use App\Entity\Exercice;
use App\Entity\Objectif;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExerciceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr'  => ['placeholder' => 'Ex: Course à pied 30 min', 'class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['placeholder' => 'Détails de l\'exercice...', 'rows' => 3, 'class' => 'form-control'],
            ])
            ->add('type', ChoiceType::class, [
                'label'   => 'Type',
                'choices' => [
                    'Cardio'           => 'cardio',
                    'Musculation'      => 'musculation',
                    'Yoga'             => 'yoga',
                    'Flexibilité'      => 'flexibilite',
                    'Sport collectif'  => 'sport_collectif',
                    'Autre'            => 'autre',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('difficulte', ChoiceType::class, [
                'label'   => 'Difficulté',
                'choices' => [
                    'Facile'   => 'facile',
                    'Moyen'    => 'moyen',
                    'Difficile' => 'difficile',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'attr'  => ['placeholder' => '30', 'min' => 1, 'class' => 'form-control'],
            ])
            ->add('date', DateType::class, [
                'label'  => 'Date',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control'],
            ])
            ->add('objectif', EntityType::class, [
                'label'        => 'Objectif',
                'class'        => Objectif::class,
                'choice_label' => 'titre',
                'placeholder'  => '-- Sélectionner un objectif --',
                'attr'         => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exercice::class,
        ]);
    }
}
