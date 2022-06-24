<?php

namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClotureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('motif', ChoiceType::class, [
                'choices' => [
                    "Problème résolu" => 'RESOLU',
                    "Non décence" => 'NON_DECENCE',
                    "Infraction RSD" => 'INFRACTION RSD',
                    "Insalubrité" => "INSALUBRITE",
                    "Logement décent" => "LOGEMENT DECENT",
                    "Locataire parti" => "LOCATAIRE PARTI",
                    "Logement vendu" => "LOGEMENT VENDU",
                    "Autre" => "AUTRE"
                ],
                'row_attr' => [
                    'class' => 'fr-select-group'
                ],
                'attr' => [
                    'class' => 'fr-select'
                ],
                'help' => 'Choisissez un motif de cloture parmis la liste ci-dessous.',
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('type', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'allow_extra_fields' => true,
            'attr' => [
                'id' => 'cloture_form'
            ],
        ]);
    }
}
