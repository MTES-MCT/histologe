<?php

namespace App\Form;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProprioType;
use App\Entity\Signalement;
use App\Entity\User;
use App\Form\Type\PhoneType;
use App\Validator\TelephoneFormat;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementDraftCoordonneesType extends AbstractType
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $nomPartner = '';
        $territory = $user->getFirstTerritory();
        if (!empty($territory)) {
            $partner = $user->getPartnerInTerritoryOrFirstOne($territory);
            $nomPartner = $partner?->getNom();
        }

        /** @var Signalement $signalement */
        $signalement = $builder->getData();
        $adresseCompleteProprio = mb_trim($signalement->getAdresseProprio().' '.$signalement->getCodePostalProprio().' '.$signalement->getVilleProprio());
        $adresseCompleteAgence = mb_trim($signalement->getAdresseAgence().' '.$signalement->getCodePostalAgence().' '.$signalement->getVilleAgence());

        $builder
            ->add('civiliteOccupant', ChoiceType::class, [
                'label' => 'Civilité',
                'choices' => [
                    'Madame' => 'mme',
                    'Monsieur' => 'mr',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
            ])
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
            ->add('telOccupant', PhoneType::class, [
                'required' => false,
                'constraints' => [
                    new TelephoneFormat([
                        'message' => 'Le numéro de téléphone n\'est pas valide.',
                        'groups' => ['bo_step_coordonnees'],
                    ]),
                ],
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
                'attr' => [
                    'data-autocomplete-bailleur-url' => $this->urlGenerator->generate('app_bailleur', ['inseecode' => $signalement->getInseeOccupant()]),
                ],
            ])
            ->add('nomProprio', TextType::class, [
                'label' => 'Nom de famille',
                'help' => 'Saisissez le nom du ou de la représentante de la société',
                'required' => false,
            ])
            ->add('prenomProprio', TextType::class, [
                'label' => 'Prénom',
                'help' => 'Saisissez le prénom du ou de la représentante de la société',
                'required' => false,
            ])
            ->add('mailProprio', TextType::class, [
                'label' => 'Adresse e-mail',
                'help' => 'Format attendu : nom@domaine.fr',
                'required' => false,
            ])
            ->add('telProprio', PhoneType::class, [
                'required' => false,
                'constraints' => [
                    new TelephoneFormat([
                        'message' => 'Le numéro de téléphone n\'est pas valide.',
                        'groups' => ['bo_step_coordonnees'],
                    ]),
                ],
            ])
            ->add('adresseCompleteProprio', null, [
                'label' => false,
                'help' => 'Format attendu : Tapez l\'adresse puis sélectionnez-la dans la liste. Si elle n\'apparaît pas, cliquez sur saisir une adresse manuellement.',
                'mapped' => false,
                'data' => $adresseCompleteProprio,
                'attr' => [
                    'autocomplete' => 'off',
                    'data-fr-adresse-autocomplete' => 'true',
                    'data-autocomplete-query-selector' => '#bo-form-signalement-coordonnees .fr-address-proprio-group',
                    'data-suffix' => 'proprio',
                ],
            ])
            ->add('adresseProprio', null, [
                'label' => 'Numéro et voie ',
                'attr' => [
                    'class' => 'bo-form-signalement-manual-address bo-form-signalement-manual-address-input',
                    'data-autocomplete-addresse-proprio' => 'true',
                ],
                'empty_data' => '',
            ])
            ->add('codePostalProprio', null, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => [
                    'class' => 'bo-form-signalement-manual-address',
                    'data-autocomplete-codepostal-proprio' => 'true',
                ],
                'empty_data' => '',
            ])
            ->add('villeProprio', null, [
                'label' => 'Ville',
                'required' => false,
                'attr' => [
                    'class' => 'bo-form-signalement-manual-address',
                    'data-autocomplete-ville-proprio' => 'true',
                ],
                'empty_data' => '',
            ])
            ->add('isProTiersDeclarant', ChoiceType::class, [
                'label' => 'Tiers professionnel',
                'choices' => [
                    'Utiliser mes coordonnées' => '1',
                ],
                'help' => 'Cochez cette case pour devenir le tiers déclarant de ce signalement. Vous recevrez alors des mises à jour par e-mail au même titre que l\'occupant du logement.',
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'attr' => [
                    'data-user-structure' => $nomPartner,
                    'data-user-nom' => $user->getNom(),
                    'data-user-prenom' => $user->getPrenom(),
                    'data-user-mail' => $user->getEmail(),
                ],
                'data' => (!empty($signalement->getMailDeclarant()) && ProfileDeclarant::TIERS_PRO === $signalement->getProfileDeclarant()) ? [$signalement->getMailDeclarant() == $user->getEmail()] : null,
            ])
            ->add('structureDeclarant', TextType::class, [
                'label' => 'Structure',
                'required' => false,
            ])
            ->add('nomDeclarant', TextType::class, [
                'label' => 'Nom de famille',
                'required' => false,
            ])
            ->add('prenomDeclarant', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
            ])
            ->add('mailDeclarant', TextType::class, [
                'label' => 'Adresse e-mail',
                'help' => 'Format attendu : nom@domaine.fr',
                'required' => false,
            ])
            ->add('telDeclarant', PhoneType::class, [
                'required' => false,
                'constraints' => [
                    new TelephoneFormat([
                        'message' => 'Le numéro de téléphone n\'est pas valide.',
                        'groups' => ['bo_step_coordonnees'],
                    ]),
                ],
            ])

            ->add('denominationAgence', TextType::class, [
                'label' => 'Dénomination',
                'required' => false,
            ])
            ->add('nomAgence', TextType::class, [
                'label' => 'Nom de famille',
                'help' => 'Saisissez le nom du ou de la gestionnaire du logement',
                'required' => false,
            ])
            ->add('prenomAgence', TextType::class, [
                'label' => 'Prénom',
                'help' => 'Saisissez le prénom du ou de la gestionnaire du logement',
                'required' => false,
            ])
            ->add('mailAgence', TextType::class, [
                'label' => 'Adresse e-mail',
                'help' => 'Format attendu : nom@domaine.fr',
                'required' => false,
            ])
            ->add('telAgence', PhoneType::class, [
                'required' => false,
                'constraints' => [
                    new TelephoneFormat([
                        'message' => 'Le numéro de téléphone n\'est pas valide.',
                        'groups' => ['bo_step_coordonnees'],
                    ]),
                ],
            ])
            ->add('adresseCompleteAgence', null, [
                'label' => false,
                'help' => 'Format attendu : Tapez l\'adresse puis sélectionnez-la dans la liste. Si elle n\'apparaît pas, cliquez sur saisir une adresse manuellement.',
                'mapped' => false,
                'data' => $adresseCompleteAgence,
                'attr' => [
                    'autocomplete' => 'off',
                    'data-fr-adresse-autocomplete' => 'true',
                    'data-autocomplete-query-selector' => '#bo-form-signalement-coordonnees .fr-address-agence-group',
                    'data-suffix' => 'agence',
                ],
            ])
            ->add('adresseAgence', null, [
                'label' => 'Numéro et voie ',
                'attr' => [
                    'class' => 'bo-form-signalement-manual-address bo-form-signalement-manual-address-input',
                    'data-autocomplete-addresse-agence' => 'true',
                ],
                'empty_data' => '',
            ])
            ->add('codePostalAgence', null, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => [
                    'class' => 'bo-form-signalement-manual-address',
                    'data-autocomplete-codepostal-agence' => 'true',
                ],
                'empty_data' => '',
            ])
            ->add('villeAgence', null, [
                'label' => 'Ville',
                'required' => false,
                'attr' => [
                    'class' => 'bo-form-signalement-manual-address',
                    'data-autocomplete-ville-agence' => 'true',
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
