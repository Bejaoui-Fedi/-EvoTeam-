<?php

namespace App\Form;

use App\Entity\Consultation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsultationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('diagnostic', TextareaType::class, [
                'label' => 'Diagnostic',
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('observation', TextareaType::class, [
                'label' => 'Observations',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('traitement', TextareaType::class, [
                'label' => 'Traitement prescrit',
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('ordonnance', TextareaType::class, [
                'label' => 'Ordonnance',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée approximative (en minutes)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'min' => 1]
            ])
            ->add('statutConsultation', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'label' => 'Statut de la consultation',
                'choices' => [
                    'Terminée' => 'Terminée',
                    'En cours' => 'En cours',
                    'Annulée' => 'Annulée',
                ],
                'attr' => ['class' => 'form-select']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consultation::class,
        ]);
    }
}
