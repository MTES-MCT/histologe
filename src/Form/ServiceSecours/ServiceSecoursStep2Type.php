<?php

namespace App\Form\ServiceSecours;

use App\Dto\ServiceSecours\FormServiceSecoursStep2;
use App\Entity\Enum\EtageType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursStep2Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('adresseCompleteOccupant', TextType::class, [
                'label' => '<span class="fr-h5">Adresse du logement <span class="text-required">*</span></span>',
                'label_html' => true,
                'help' => 'Format attendu : Tapez l\'adresse puis sélectionnez-la dans la liste. Si elle n\'apparaît pas, cliquez sur saisir une adresse manuellement.',
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'off',
                    'data-fr-adresse-autocomplete' => 'true',
                    'data-autocomplete-query-selector' => '#fo-form-service-secours-adresse .fr-address-group',
                ],
            ])
            ->add('adresseOccupant', TextType::class, [
                'label' => 'Numéro et voie',
                'required' => false,
                'empty_data' => '',
                'attr' => [
                    'class' => 'manual-address manual-address-input',
                    'data-autocomplete-addresse' => 'true',
                ],
            ])
            ->add('cpOccupant', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'empty_data' => '',
                'attr' => [
                    'class' => 'manual-address',
                    'data-autocomplete-codepostal' => 'true',
                ],
            ])
            ->add('villeOccupant', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'empty_data' => '',
                'attr' => [
                    'class' => 'manual-address',
                    'data-autocomplete-ville' => 'true',
                ],
            ])
            ->add('inseeOccupant', HiddenType::class, [
                'attr' => [
                    'data-autocomplete-insee' => 'true',
                ],
            ])

            ->add('adresseAutreOccupant', TextType::class, [
                'label' => 'Complément d\'adresse',
                'help' => 'Lieu-dit, bâtiment, étage, porte, ...<br>Format attendu : 255 caractères maximum',
                'help_html' => true,
                'required' => false,
            ])

            ->add('isLogementSocial', ChoiceType::class, [
                'label' => 'Logement social <span class="text-required">*</span>',
                'label_html' => true,
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
                // chaines pour éviter le null par défaut qui sélectionnait "Indéterminé"
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                    'Indéterminé' => 'nsp',
                ],
            ])
            ->add('natureLogement', ChoiceType::class, [
                'label' => 'Nature du logement <span class="text-required">*</span>',
                'label_html' => true,
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
                'choices' => [
                    'Appartement' => 'appartement',
                    'Maison' => 'maison',
                    'Autre' => 'autre',
                ],
            ])
            ->add('natureLogementAutre', TextType::class, [
                'label' => 'Préciser la nature du logement <span class="text-required">*</span>',
                'label_html' => true,
                'help' => 'Format attendu : 15 caractères maximum',
                'required' => false,
            ])
            ->add('typeEtageLogement', EnumType::class, [
                'class' => EtageType::class,
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
                'label' => 'Localisation de l\'appartement',
            ])
            ->add('etageOccupant', TextType::class, [
                'label' => 'Préciser l\'étage',
                'help' => 'Format attendu : 5 caractères maximum',
                'required' => false,
            ])
            ->add('nbPiecesLogement', TextType::class, [
                'label' => 'Nombre de pièces à vivre (salon, chambre) du logement',
                'help' => 'Format attendu : Saisir un nombre entier',
                'required' => false,
            ])
            ->add('superficie', TextType::class, [
                'label' => 'Superficie approximative du logement (en m²)',
                'help' => 'Format attendu : Saisir un nombre entier',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormServiceSecoursStep2::class,
        ]);
    }
}
