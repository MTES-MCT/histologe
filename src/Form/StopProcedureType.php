<?php

namespace App\Form;

use App\Entity\Enum\MotifCloture;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class StopProcedureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', ChoiceType::class, [
                'choices' => MotifCloture::getListForBailleur(),
                'choice_label' => fn (MotifCloture $choice) => $choice->label(),
                'label' => 'Précisez la raison pour laquelle vous souhaitez arrêter la procédure',
                'placeholder' => '',
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Veuillez choisir une raison.'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Précisez la situation',
                'help' => 'Dix (10) caractères minimum',
                'attr' => [
                    'rows' => 5,
                ],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Veuillez fournir une description de la situation.'),
                    new Assert\Length(
                        min: 10,
                        minMessage: 'Le message doit contenir au moins {{ limit }} caractères.',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
