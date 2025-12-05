<?php

namespace App\Form;

use App\Entity\Signalement;
use App\Form\Type\PhoneType;
use App\Validator\TelephoneFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UsagerCoordonneesTiersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomDeclarant', TextType::class, [
                'label' => 'Nom de famille',
                'required' => false,
                'attr' => ['maxlength' => 50],
            ])
            ->add('prenomDeclarant', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => ['maxlength' => 50],
            ])
            ->add('mailDeclarant', TextType::class, [
                'label' => 'Adresse e-mail',
                'help' => 'Format attendu : nom@domaine.fr',
                'required' => false,
            ])
            ->add('telDeclarant', PhoneType::class, [
                'label' => 'Numéro de téléphone (facultatif)',
                'required' => false,
                'constraints' => [
                    new TelephoneFormat([
                        'message' => 'Le numéro de téléphone n\'est pas valide.',
                    ]),
                ],
            ]);

        $builder->add('save', SubmitType::class, [
            'label' => 'Inviter le tiers',
            'attr' => [
                'class' => 'fr-btn--primary',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['fo_suivi_usager_tiers'],
            'data_class' => Signalement::class,
            'extended' => false,
        ]);
    }
}
