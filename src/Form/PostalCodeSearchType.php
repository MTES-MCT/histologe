<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PostalCodeSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('postalcode', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group fr-col-12 fr-col-md-6 fr-mb-0',
                ],
                'attr' => [
                    'class' => 'fr-input',
                    'placeholder' => 'Ex. : 59000',
                    'pattern' => '[0-9]{5}',
                    'title' => 'Le code postal doit être composé de 5 chiffres.',
                ],
                'label' => 'Votre code postal',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{5}$/',
                        'message' => 'Le code postal doit être composé de 5 chiffres.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'id' => 'front_postalcodesearch',
                'class' => 'needs-validation',
            ],
        ]);
    }
}
