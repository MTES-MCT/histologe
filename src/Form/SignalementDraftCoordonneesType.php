<?php

namespace App\Form;

use App\Entity\Enum\ProprioType;
use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignalementDraftCoordonneesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Signalement $signalement */
        $signalement = $builder->getData();
        $adresseCompleteProprio = trim($signalement->getAdresseProprio().' '.$signalement->getCodePostalProprio().' '.$signalement->getVilleProprio());

        $builder
        /*
            ->add('bail', ChoiceType::class, [
                'label' => 'Contrat de location (bail)',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $bail,
            ])
            ->add('dateEntreeLogement', DateType::class, [
                'label' => 'Date d\'entrée dans le logement',
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $dateEntreeLogement,
            ])
            ->add('montantLoyer', NumberType::class, [
                'label' => 'Montant du loyer',
                'help' => 'Format attendu : saisir un nombre entier',
                'required' => false,
                'mapped' => false,
                'data' => $montantLoyer,
            ])
            ->add('reponseProprietaire', TextareaType::class, [
                'label' => 'Réponse du bailleur / propriétaire',
                'help' => 'Format attendu : 10 caractères minimum',
                'required' => false,
                'mapped' => false,
                'data' => $reponseProprietaire,
            ])
                */
            ->add('nomOccupant', TextType::class, [
                'label' => 'Nom de famille',
                'required' => false,
            ])
            ->add('prenomOccupant', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
            ])
            ->add('mailOccupant', TextType::class, [
                'label' => 'Adresse e-mail',
                'help' => 'Format attendu : nom@domaine.fr',
                'required' => false,
            ])
            ->add('telOccupant', TextType::class, [
                'label' => 'Téléphone',
                'help' => 'Format attendu : Veuillez sélectionner le pays pour obtenir l\'indicatif téléphonique, puis saisir le numéro de téléphone au format national (sans l\'indicatif). Exemple pour la France : 0702030405.',
                'required' => false,
            ])

            ->add('typeProprio', EnumType::class, [
                'label' => 'Type de bailleur',
                'class' => ProprioType::class,
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
            ])
            ->add('denominationProprio', TextType::class, [
                'label' => 'Dénomination',
                'required' => false,
            ])
            ->add('nomProprio', TextType::class, [
                'label' => 'Nom de famille',
                'help' => 'Saisissez le nom du ou de la représentante de la société',
                'required' => false,
            ])
            ->add('prenomProprio', TextType::class, [
                'label' => 'Prénom de famille',
                'help' => 'Saisissez le prénom du ou de la représentante de la société',
                'required' => false,
            ])
            ->add('mailProprio', TextType::class, [
                'label' => 'Adresse e-mail',
                'help' => 'Format attendu : nom@domaine.fr',
                'required' => false,
            ])
            ->add('telProprio', TextType::class, [
                'label' => 'Téléphone',
                'help' => 'Format attendu : Veuillez sélectionner le pays pour obtenir l\'indicatif téléphonique, puis saisir le numéro de téléphone au format national (sans l\'indicatif). Exemple pour la France : 0702030405.',
                'required' => false,
            ])
            ->add('adresseCompleteProprio', null, [
                'label' => false,
                'help' => 'Format attendu : Tapez l\'adresse puis sélectionnez-la dans la liste. Si elle n\'apparaît pas, cliquez sur saisir une adresse manuellement.',
                'mapped' => false,
                'data' => $adresseCompleteProprio,
                'attr' => [
                    'autocomplete' => 'off',
                    'data-fr-adresse-autocomplete' => 'true',
                    'data-autocomplete-query-selector' => '#bo-form-signalement-coordonnees .fr-address-group',
                ],
            ])
            ->add('adresseProprio', null, [
                'label' => 'Numéro et voie ',
                'attr' => [
                    'class' => 'bo-form-signalement-manual-address',
                ],
                'empty_data' => '',
            ])
            ->add('codePostalProprio', null, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => [
                    'class' => 'bo-form-signalement-manual-address',
                ],
                'empty_data' => '',
            ])
            ->add('villeProprio', null, [
                'label' => 'Ville',
                'required' => false,
                'attr' => [
                    'class' => 'bo-form-signalement-manual-address',
                ],
                'empty_data' => '',
            ])

            ->add('forceSave', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('previous', SubmitType::class, [
                'label' => 'Précédent',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-left-line fr-btn--icon-left fr-btn--secondary', 'data-target' => 'situation'],
                'row_attr' => ['class' => 'fr-ml-2w'],
            ])
            ->add('draft', SubmitType::class, [
                'label' => 'Finir plus tard',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-go-forward-line fr-btn--icon-left fr-btn--tertiary-no-outline'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Suivant',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-right-line fr-btn--icon-right', 'data-target' => 'desordres'],
                'row_attr' => ['class' => 'fr-ml-2w'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['bo_step_coordonnees'],
        ]);
    }
}
