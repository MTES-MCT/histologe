<?php

namespace App\Form;

use App\Entity\Situation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SituationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ]
            ])
            ->add('isActive', ChoiceType::class, [
                'row_attr' => [
                    'class' => 'fr-select-group'
                ], 'attr' => [
                    'class' => 'fr-select'
                ],
                'choices' => [
                    'Active' => 1,
                    'Desactivée' => 0
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => 'Active'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Situation::class,
            'allow_extra_fields' => true
        ]);
    }
}
