<?php

namespace App\Form;

use App\Entity\Evenement;
use App\Entity\Participation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['include_event']) {
            $builder->add('event', EntityType::class, [
                'class' => Evenement::class,
                'choice_label' => 'name',
                'label' => 'Evenement',
                'placeholder' => 'Selectionnez un evenement',
            ]);
        }

        $builder
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Inscrit' => 'registered',
                    'Annule' => 'cancelled',
                ],
            ])
            ->add('seats', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => ['min' => 1],
            ])
            ->add('contactPhone', TextType::class, [
                'label' => 'Téléphone de contact',
                'required' => false,
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Paiement sur place' => 'on_site',
                    'Paiement en ligne (Stripe)' => 'online',
                ],
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note / commentaire',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participation::class,
            'include_event' => false,
        ]);
    }
}
