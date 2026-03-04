<?php

namespace App\Form\SignalementeEditFO;

use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdresseLogementType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('etageOccupant', TextType::class, [
                'label' => 'Étage',
                'required' => false,
            ])
            ->add('escalierOccupant', TextType::class, [
                'label' => 'Escalier',
                'required' => false,
            ])
            ->add('numAppartOccupant', TextType::class, [
                'label' => 'Numéro d\'appartement',
                'required' => false,
            ])
            ->add('adresseAutreOccupant', TextType::class, [
                'label' => 'Autre',
                'required' => false,
            ]);

        $builder->add('save', SubmitType::class, [
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
            'extended' => false,
        ]);
    }
}
