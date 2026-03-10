<?php

namespace App\Form\SignalementeEditFO;

use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CoordonneesOccupantType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Signalement $signalement */
        $signalement = $builder->getData();
        $mailOccupant = $signalement->getMailOccupant();
        $builder
            ->add('civiliteOccupant', ChoiceType::class, [
                'label' => 'Civilité',
                'choices' => [
                    'M' => 'M',
                    'Mme' => 'Mme',
                ],
                'required' => false,
            ])
            ->add('nomOccupant', null, [
                'label' => 'Nom <span class="text-required">*</span>',
                'label_html' => true,
                'required' => false,
            ])
            ->add('prenomOccupant', null, [
                'label' => 'Prénom <span class="text-required">*</span>',
                'label_html' => true,
                'required' => false,
            ]);
        $constraintsMailOccupant = [
            new Assert\Email([
                'mode' => Assert\Email::VALIDATION_MODE_STRICT,
                'message' => 'L\'adresse e-mail de l\'occupant n\'est pas valide.',
                'groups' => ['fo_coordonnees_occupant'],
            ]),
        ];
        $labelMailOccupant = 'Adresse e-mail';
        // mail obligatoire si occupant déclarant
        if (!$signalement->isTiersDeclarant()) {
            $constraintsMailOccupant[] = new Assert\NotBlank([
                'message' => 'L\'adresse e-mail est obligatoire.',
                'groups' => ['fo_coordonnees_occupant'],
            ]);
            $labelMailOccupant .= ' <span class="text-required">*</span>';
        }
        $builder->add('mailOccupantTemp', TextType::class, [
            'label' => $labelMailOccupant,
            'label_html' => true,
            'required' => false,
            'mapped' => false,
            'data' => $mailOccupant,
            'constraints' => $constraintsMailOccupant,
        ])
        ->add('telOccupant', null, [
            'label' => 'Numéro de téléphone',
            'required' => false,
        ])
        ->add('telOccupantBis', null, [
            'label' => 'Numéro de téléphone secondaire',
            'required' => false,
        ])
        ->add('save', SubmitType::class, [
            'label' => 'Envoyer',
            'attr' => [
                'class' => 'fr-btn--primary',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Signalement::class,
            'validation_groups' => ['fo_coordonnees_occupant'],
        ]);
    }
}
