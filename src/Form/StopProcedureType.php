<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class StopProcedureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Commentaire',
                'help' => 'Dix (10) caractères minimum',
                'attr' => [
                    'rows' => 5,
                ],
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(
                        min: 10,
                        minMessage: 'Le message doit contenir au moins {{ limit }} caractères.',
                    ),
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Confirmer',
                'attr' => [
                    'class' => 'fr-btn--primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
