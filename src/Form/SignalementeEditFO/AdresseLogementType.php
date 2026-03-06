<?php

namespace App\Form\SignalementeEditFO;

use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

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
                'help' => 'Format attendu : 5 caractères maximum',
                'constraints' => [
                    new Assert\Length([
                        'max' => 5,
                        'maxMessage' => 'L\'étage doit contenir au maximum {{ limit }} caractères.',
                    ]),
                ],
                'required' => false,
            ])
            ->add('escalierOccupant', TextType::class, [
                'label' => 'Escalier',
                'help' => 'Format attendu : 3 caractères maximum',
                'constraints' => [
                    new Assert\Length([
                        'max' => 3,
                        'maxMessage' => 'L\'escalier doit contenir au maximum {{ limit }} caractères.',
                    ]),
                ],
                'required' => false,
            ])
            ->add('numAppartOccupant', TextType::class, [
                'label' => 'Numéro d\'appartement',
                'help' => 'Format attendu : 5 caractères maximum',
                'constraints' => [
                    new Assert\Length([
                        'max' => 5,
                        'maxMessage' => 'Le numéro d\'appartement doit contenir au maximum {{ limit }} caractères.',
                    ]),
                ],
                'required' => false,
            ])
            ->add('adresseAutreOccupant', TextType::class, [
                'label' => 'Autre',
                'help' => 'Format attendu : 255 caractères maximum',
                'constraints' => [
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Les autres informations sur l\'adresse doivent contenir au maximum {{ limit }} caractères.',
                    ]),
                ],
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
