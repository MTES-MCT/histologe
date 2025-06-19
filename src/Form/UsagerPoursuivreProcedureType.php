<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UsagerPoursuivreProcedureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('details', TextareaType::class, [
                'label' => 'Souhaitez-vous apporter des précisons à votre demande ?',
                'help' => 'Dix (10) caractères minimum',
                'required' => false,
                'constraints' => [
                    new Assert\Length(
                        min: 10,
                        minMessage: 'Le message doit contenir au moins {{ limit }} caractères.',
                    ),
                ],
            ]);
    }
}
