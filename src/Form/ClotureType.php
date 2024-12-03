<?php

namespace App\Form;

use App\Entity\Enum\MotifCloture;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClotureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('motif', EnumType::class, [
                'class' => MotifCloture::class,
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'row_attr' => [
                    'class' => 'fr-select-group',
                ],
                'placeholder' => 'Sélectionner un motif',
                'attr' => [
                    'class' => 'fr-select',
                ],
                'help' => 'Choisissez un motif de cloture parmi la liste ci-dessous.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
            ])
            ->add('type', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'allow_extra_fields' => true,
            'attr' => [
                'id' => 'cloture_form',
            ],
        ]);
    }
}
