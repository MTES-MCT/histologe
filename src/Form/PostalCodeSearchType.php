<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostalCodeSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('postalcode', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group fr-col-12 fr-col-md-6 fr-mb-0'
                ],
                'attr' => [
                    'class' => 'fr-input',
                    'placeholder' => 'Ex. : 59000',
                    'minlength' => 2,
                    'maxlength' => 5,
                ],
                'label' => 'Votre code postal'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'id' => 'front_postalcodesearch',
                'class' => 'needs-validation',
                'novalidate' => 'true',
            ]
        ]);
    }
}
