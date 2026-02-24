<?php

namespace App\Form\ServiceSecours;

use App\Dto\ServiceSecours\FormServiceSecoursStep2;
use App\Entity\Enum\EtageType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursStep2Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('adresseCompleteOccupant', null, [
                'label' => 'Adresse du logement <span class="text-required">*</span>',
                'label_html' => true,
                'help' => 'Format attendu : Tapez l\'adresse puis sélectionnez-la dans la liste. Si elle n\'apparaît pas, cliquez sur saisir une adresse manuellement.',
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'off',
                    'data-fr-adresse-autocomplete' => 'true',
                    'data-autocomplete-query-selector' => '#fo-form-service-secours-adresse .fr-address-group',
                ],
            ])
            ->add('adresseOccupant', null, [
                'label' => 'Numéro et voie',
                'empty_data' => '',
                'attr' => [
                    'class' => 'manual-address manual-address-input',
                    'data-autocomplete-addresse' => 'true',
                ],
            ])
            ->add('cpOccupant', null, [
                'label' => 'Code postal',
                'required' => false,
                'empty_data' => '',
                'attr' => [
                    'class' => 'manual-address',
                    'data-autocomplete-codepostal' => 'true',
                ],
            ])
            ->add('villeOccupant', null, [
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

            ->add('adresseAutreOccupant', null, [
                'label' => 'Complément d\'adresse',
                'help' => 'Lieu-dit, bâtiment, étage, porte, ...<br>Format attendu : 255 caractères maximum',
                'help_html' => true,
            ])

            ->add('isLogementSocial', ChoiceType::class, [
                'label' => 'Logement social <span class="text-required">*</span>',
                'label_html' => true,
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                    'Indéterminé' => null,
                ],
            ])
            ->add('natureLogement', ChoiceType::class, [
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
                'choices' => [
                    'Appartement' => 'appartement',
                    'Maison' => 'maison',
                    'Autre' => 'autre',
                ],
                'label' => 'Nature du logement <span class="text-required">*</span>',
                'label_html' => true,
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
            ->add('etageOccupant', null, [
                'label' => 'Préciser l\'étage',
                'help' => 'Format attendu : 5 caractères maximum',
            ])
            ->add('nbPiecesLogement', null, [
                'label' => 'Nombre de pièces à vivre (salon, chambre) du logement',
                'help' => 'Format attendu : Saisir un nombre entier',
            ])
            ->add('superficie', null, [
                'label' => 'Superficie approximative du logement (en m²)',
                'help' => 'Format attendu : Saisir un nombre entier',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormServiceSecoursStep2::class,
        ]);
    }
}
