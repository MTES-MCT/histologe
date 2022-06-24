<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group fr-col-6'
                ], 'attr' => [
                    'class' => 'fr-input'
                ], 'label' => 'Votre nom'
            ])
            ->add('email', EmailType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group fr-col-6'
                ], 'attr' => [
                    'class' => 'fr-input'
                ], 'label' => 'Votre adresse courriel'
            ])
            ->add('message', TextareaType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group fr-col-12'
                ], 'attr' => [
                    'class' => 'fr-input',
                    'rows' => 10,
                    'minlength' => 10
                ], 'label' => 'Votre message'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'id' => 'front_contact',
                'class' => 'needs-validation',
                'novalidate' => 'true',
            ]
            // Configure your form options here
        ]);
    }
}
