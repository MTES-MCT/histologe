<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class UsagerPoursuivreProcedureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('details', TextareaType::class, [
                'label' => 'Souhaitez-vous apporter des précisons à votre demande ?',
                'help' => 'Dix (10) caractères minimum',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Valider ma demande',
                'attr' => ['class' => 'fr-btn fr-icon-check-line'],
            ]);
    }
}
