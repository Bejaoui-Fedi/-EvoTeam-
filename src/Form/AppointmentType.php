<?php

namespace App\Form;

use App\Entity\Appointment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppointmentType extends AbstractType
{
    private \App\Repository\UserRepository $userRepository;

    public function __construct(\App\Repository\UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $pros = $this->userRepository->createQueryBuilder('u')
            ->where('u.role = :role')
            ->setParameter('role', 'PSY_COACH')
            ->getQuery()
            ->getResult();

        $proChoices = [];
        foreach ($pros as $pro) {
            $label = $pro->getNom() . ' (Psychologue / Coach)';
            $proChoices[$label] = $pro->getId();
        }

        $builder
            ->add('dateRdv', DateType::class, [
                'widget' => 'single_text',
                'label' => '📅 Date de consultation souhaitée',
                'attr' => ['class' => 'elite-input', 'min' => (new \DateTime())->format('Y-m-d')]
            ])
            ->add('heureRdv', TimeType::class, [
                'widget' => 'single_text',
                'label' => '⏰ Heure du rendez-vous',
                'attr' => ['class' => 'elite-input']
            ])
            ->add('motif', TextareaType::class, [
                'label' => '📝 Objet de votre visite',
                'attr' => [
                    'class' => 'elite-textarea', 
                    'rows' => 4, 
                    'placeholder' => 'Décrivez brièvement vos attentes ou symptômes pour aider notre spécialiste...'
                ]
            ])
            ->add('typeRdv', ChoiceType::class, [
                'label' => '📍 Mode de consultation',
                'choices' => [
                    '🏢 Au cabinet (Présentiel)' => 'Présentiel',
                    '💻 Session Vidéo (En ligne)' => 'En ligne',
                ],
                'attr' => ['class' => 'elite-select']
            ])
            ->add('professionalId', ChoiceType::class, [
                'label' => '👨‍⚕️ Votre Spécialiste Dédié',
                'choices' => $proChoices,
                'placeholder' => 'Sélectionnez un membre de notre équipe...',
                'attr' => ['class' => 'elite-select']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
        ]);
    }
}
