<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UsagerCancelProcedureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', ChoiceType::class, [
                'label' => 'Pour quelle raison voulez-vous arrêter la procédure ?',
                'choices' => [
                    'Le problème est résolu' => 'Le problème est résolu',
                    'Changement de logement' => 'Changement de logement',
                    'Accord avec le propriétaire' => 'Accord avec le propriétaire',
                    'Autre' => 'Autre',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Veuillez choisir une raison.',
                    ),
                ],
            ])
            ->add('details', TextareaType::class, [
                'label' => 'Veuillez détailler la raison pour laquelle vous souhaitez arrêter la procédure',
                'help' => 'Dix (10) caractères minimum',
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Veuillez détailler la raison.',
                    ),
                    new Assert\Length(
                        min: 10,
                        minMessage : 'Le message doit contenir au moins {{ limit }} caractères.',
                    ),
                ],
            ]);
    }
}
