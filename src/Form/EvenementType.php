<?php

namespace App\Form;

use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Entrez le nom de l\'événement']
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('maxParticipants', NumberType::class, [
                'label' => 'Places maximum',
                'attr' => ['min' => 1, 'max' => 1000]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 5]
            ])
            ->add('fee', NumberType::class, [
                'label' => 'Prix (€)',
                'scale' => 2,
                'attr' => ['step' => 0.01, 'min' => 0]
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
                'attr' => ['placeholder' => 'Entrez le lieu']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Créer l\'événement',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}