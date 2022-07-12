<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostalCodeSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('postalcode', NumberType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group fr-col-12 fr-col-md-6'
                ],
                'attr' => [
                    'class' => 'fr-input',
                    'placeholder' => 'Ex. : 59000',
                    'minlength' => 2,
                    'maxlength' => 5,
                ],
                'label' => 'Votre code postal',
                'label_attr' => [
                    'class' => 'form-home-text-field',
                ]
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
