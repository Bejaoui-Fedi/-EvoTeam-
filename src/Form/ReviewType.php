<?php

namespace App\Form;

use App\Entity\Evenement;
use App\Entity\Review;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Entrez le titre de l\'avis']
            ])
            ->add('rating', IntegerType::class, [
                'label' => 'Note',
                'attr' => ['min' => 1, 'max' => 5],
                'help' => 'Note entre 1 et 5'
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => ['rows' => 5, 'placeholder' => 'Votre commentaire...']
            ]);

        if ($options['include_event']) {
            $builder->add('event', EntityType::class, [
                'class' => Evenement::class,
                'choice_label' => 'name',
                'label' => 'Evenement',
                'placeholder' => 'Selectionnez un evenement',
            ]);
        }

        $builder
            ->add('save', SubmitType::class, [
                'label' => 'Créer l\'avis',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
            'include_event' => true,
        ]);
    }
}