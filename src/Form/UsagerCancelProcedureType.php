<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class UsagerCancelProcedureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', ChoiceType::class, [
                'choices' => [
                    'Le problème est résolu' => 'Le problème est résolu',
                    'Changement de logement' => 'Changement de logement',
                    'Accord avec le propriétaire' => 'Accord avec le propriétaire',
                    'Autre' => 'Autre',
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'Pour quelle raison voulez-vous arrêter la procédure ?',
                'required' => true,
            ])
            ->add('details', TextareaType::class, [
                'label' => 'Veuillez détailler la raison pour laquelle vous souhaitez arrêter la procédure',
                'help' => 'Dix (10) caractères minimum',
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Valider ma demande',
                'attr' => ['class' => 'fr-btn fr-icon-check-line'],
            ]);
    }
}
