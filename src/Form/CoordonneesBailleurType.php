<?php

namespace App\Form;

use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CoordonneesBailleurType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Signalement $signalement */
        $signalement = $builder->getData();
        $adresseCompleteProprio = mb_trim($signalement->getAdresseProprio().' '.$signalement->getCodePostalProprio().' '.$signalement->getVilleProprio());

        if ($options['extended']) {
            $builder
                ->add('nomProprio', TextType::class, [
                    'label' => 'Nom',
                    'disabled' => true,
                ])
                ->add('prenomProprio', TextType::class, [
                    'label' => 'Prénom',
                    'disabled' => true,
                ])
                ->add('adresseCompleteProprio', null, [
                    'label' => false,
                    'help' => 'Format attendu : Tapez l\'adresse puis sélectionnez-la dans la liste. Si elle n\'apparaît pas, cliquez sur saisir une adresse manuellement.',
                    'mapped' => false,
                    'data' => $adresseCompleteProprio,
                    'attr' => [
                        'autocomplete' => 'off',
                        'data-fr-adresse-autocomplete' => 'true',
                        'data-autocomplete-query-selector' => '#form-usager-complete-dossier .fr-address-proprio-group',
                        'data-suffix' => 'proprio',
                    ],
                ])
                ->add('adresseProprio', null, [
                    'label' => 'Numéro et voie ',
                    'attr' => [
                        'class' => 'manual-address manual-address-input',
                        'data-autocomplete-addresse-proprio' => 'true',
                    ],
                    'empty_data' => '',
                ])
                ->add('codePostalProprio', null, [
                    'label' => 'Code postal',
                    'required' => false,
                    'attr' => [
                        'class' => 'manual-address',
                        'data-autocomplete-codepostal-proprio' => 'true',
                    ],
                    'empty_data' => '',
                ])
                ->add('villeProprio', null, [
                    'label' => 'Ville',
                    'required' => false,
                    'attr' => [
                        'class' => 'manual-address',
                        'data-autocomplete-ville-proprio' => 'true',
                    ],
                    'empty_data' => '',
                ])
                ->add('telProprio', TextType::class, [
                    'label' => 'Téléphone principal',
                    'required' => false,
                ])
                ->add('telProprioSecondaire', TextType::class, [
                    'label' => 'Téléphone secondaire',
                    'required' => false,
                ])
                ->add('mailProprio', TextType::class, [
                    'label' => 'Adresse e-mail',
                    'required' => false,
                    'help' => 'Format attendu : nom@domaine.fr',
                ]);
        } else {
            $builder
                ->add('mailProprio', TextType::class, [
                    'label' => 'Afin de fluidifier les échanges, merci de renseigner votre adresse e-mail.',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ]);
        }

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
