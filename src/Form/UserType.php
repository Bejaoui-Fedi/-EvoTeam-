<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'Name', 'attr' => ['class' => 'form-control']])
            ->add('email', EmailType::class, ['label' => 'Email', 'attr' => ['class' => 'form-control']])
            ->add('password', PasswordType::class, ['label' => 'Password (leave blank to keep current)', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('role', ChoiceType::class, [
                'label' => 'Role',
                'choices' => [
                    'User' => 'user',
                    'Admin' => 'admin',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('telephone', TextType::class, ['label' => 'Phone', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('actif', CheckboxType::class, ['label' => 'Active', 'required' => false, 'attr' => ['class' => 'form-check-input']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
