<?php

namespace App\Form;

use App\Entity\TiersInvitation;
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
            ->add('lastname', TextType::class, [
                'label' => 'Nom de famille',
                'required' => false,
                'attr' => ['maxlength' => 50],
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => ['maxlength' => 50],
            ])
            ->add('email', TextType::class, [
                'label' => 'Adresse e-mail',
                'help' => 'Format attendu : nom@domaine.fr',
                'required' => false,
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Numéro de téléphone (facultatif)',
                'required' => false,
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
            'data_class' => TiersInvitation::class,
            'extended' => false,
        ]);
    }
}
