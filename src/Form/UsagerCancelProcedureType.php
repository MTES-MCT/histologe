<?php

namespace App\Form;

use App\Entity\Enum\MotifClotureUsager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UsagerCancelProcedureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', EnumType::class, [
                'class' => MotifClotureUsager::class,
                'label' => 'Pour quelle raison voulez-vous arrêter la procédure ?',
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'choice_label' => fn (MotifClotureUsager $choice) => $choice->label(),
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
