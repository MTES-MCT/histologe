<?php

namespace App\Form;

use App\Entity\Enum\OccupantLink;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SignalementDraftAddressType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $signalement = $builder->getData();
        $adresseCompleteOccupant = '';
        if ($signalement->getBanIdOccupant()) {
            $adresseCompleteOccupant = mb_trim($signalement->getAdresseOccupant().' '.$signalement->getCpOccupant().' '.$signalement->getVilleOccupant());
        }
        $nbEnfantsDansLogement = $signalement->getTypeCompositionLogement()?->getCompositionLogementNombreEnfants();
        $enfantsDansLogementMoinsSixAns = $signalement->getTypeCompositionLogement()?->getCompositionLogementEnfants();

        /** @var User $user */
        $user = $this->security->getUser();
        if ($user->isSuperAdmin()) {
            $territories = [];
        } else {
            $territories = $user->getPartnersTerritories();
        }
        if (1 === \count($territories)) {
            $territory = $user->getFirstTerritory();
            $builder->add('filterSearchAddressTerritory', HiddenType::class, [
                'mapped' => false,
                'data' => $territory->getZip(),
            ]);
        }

        $builder
            ->add('adresseCompleteOccupant', null, [
                'label' => false,
                'help' => 'Format attendu : Tapez l\'adresse puis sélectionnez-la dans la liste. Si elle n\'apparaît pas, cliquez sur saisir une adresse manuellement.',
                'mapped' => false,
                'data' => $adresseCompleteOccupant,
                'attr' => [
                    'autocomplete' => 'off',
                    'data-fr-adresse-autocomplete' => 'true',
                    'data-autocomplete-query-selector' => '#bo-form-signalement-adresse .fr-address-group',
                ],
            ])
            ->add('adresseOccupant', null, [
                'label' => 'Numéro et voie ',
                'attr' => [
                    'class' => 'bo-form-signalement-manual-address bo-form-signalement-manual-address-input',
                ],
                'empty_data' => '',
            ])
            ->add('cpOccupant', null, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => [
                    'class' => 'bo-form-signalement-manual-address',
                ],
                'empty_data' => '',
            ])
            ->add('villeOccupant', null, [
                'label' => 'Ville',
                'required' => false,
                'attr' => [
                    'class' => 'bo-form-signalement-manual-address',
                ],
                'empty_data' => '',
            ])
            ->add('etageOccupant', null, [
                'label' => 'Étage',
                'help' => 'Format attendu : 5 caractères maximum',
            ])
            ->add('escalierOccupant', null, [
                'label' => 'Escalier',
                'help' => 'Format attendu : 3 caractères maximum',
            ])
            ->add('numAppartOccupant', null, [
                'label' => 'Numéro d\'appartement',
                'help' => 'Format attendu : 5 caractères maximum',
            ])
            ->add('adresseAutreOccupant', null, [
                'label' => 'Autre',
                'help' => 'Format attendu : 255 caractères maximum',
                'attr' => [
                    'placeholder' => 'résidence, lieu-dit...',
                ],
            ])
            ->add('profileDeclarant', ChoiceType::class, [
                'label' => 'Déclarant <span class="text-required">*</span>',
                'label_html' => true,
                'choices' => [
                    ProfileDeclarant::TIERS_PARTICULIER->label() => ProfileDeclarant::TIERS_PARTICULIER->value,
                    ProfileDeclarant::TIERS_PRO->label() => ProfileDeclarant::TIERS_PRO->value,
                    ProfileDeclarant::SERVICE_SECOURS->label() => ProfileDeclarant::SERVICE_SECOURS->value,
                    ProfileDeclarant::BAILLEUR->label() => ProfileDeclarant::BAILLEUR->value,
                    ProfileDeclarant::BAILLEUR_OCCUPANT->label() => ProfileDeclarant::BAILLEUR_OCCUPANT->value,
                    ProfileDeclarant::LOCATAIRE->label() => ProfileDeclarant::LOCATAIRE->value,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $signalement->getProfileDeclarant()?->value ?? '',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez renseigner le profil du déclarant.',
                        'groups' => ['bo_step_address'],
                    ]),
                ],
            ])
            ->add('lienDeclarantOccupant', ChoiceType::class, [
                'label' => 'Lien avec l\'occupant',
                'choices' => [
                    OccupantLink::PROCHE->label() => OccupantLink::PROCHE->value,
                    OccupantLink::VOISIN->label() => OccupantLink::VOISIN->value,
                    OccupantLink::AUTRE->label() => OccupantLink::AUTRE->value,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $signalement->getLienDeclarantOccupant(),
            ])
            ->add('isLogementSocial', ChoiceType::class, [
                'label' => 'Logement social <span class="text-required">*</span>',
                'label_html' => true,
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Veuillez renseigner si il s\'agit d\'un logement social.',
                        'groups' => ['bo_step_address'],
                    ]),
                ],
            ])
            ->add('logementVacant', ChoiceType::class, [
                'label' => 'S\'agit-il d\'un logement vacant ?',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $signalement->getIsLogementVacant(),
            ])
            ->add('nbOccupantsLogement', NumberType::class, [
                'label' => 'Nombre de personnes occupant le logement',
                'help' => 'Format attendu : saisir un nombre entier',
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^\d+$/',
                        'message' => 'Veuillez saisir un nombre entier.',
                        'groups' => ['bo_step_address'],
                    ]),
                ],
            ])
            ->add('nbEnfantsDansLogement', NumberType::class, [
                'label' => 'Dont enfants',
                'help' => 'Format attendu : saisir un nombre entier',
                'required' => false,
                'mapped' => false,
                'data' => $nbEnfantsDansLogement,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^\d+$/',
                        'message' => 'Veuillez saisir un nombre entier.',
                        'groups' => ['bo_step_address'],
                    ]),
                ],
            ])
            ->add('enfantsDansLogementMoinsSixAns', ChoiceType::class, [
                'label' => 'Enfants de 6 ans ou moins',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $enfantsDansLogementMoinsSixAns,
            ])
            ->add('forceSave', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('draft', SubmitType::class, [
                'label' => 'Finir plus tard',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-go-forward-line fr-btn--icon-left fr-btn--tertiary-no-outline'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Suivant',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-right-line fr-btn--icon-right', 'data-target' => 'logement'],
                'row_attr' => ['class' => 'fr-ml-2w'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Signalement::class,
            'constraints' => [
                new Assert\Callback([$this, 'validateAddress'], ['bo_step_address']),
            ],
            'validation_groups' => ['bo_step_address'],
        ]);
    }

    public function validateAddress(mixed $value, ExecutionContextInterface $context): void
    {
        $form = $context->getRoot();
        $manualAddressEmpty = $form->get('adresseOccupant')->isEmpty() && $form->get('cpOccupant')->isEmpty() && $form->get('villeOccupant')->isEmpty();
        if ($form->get('adresseCompleteOccupant')->isEmpty() && $manualAddressEmpty) {
            $form->get('adresseCompleteOccupant')->addError(new FormError('Veuillez renseigner une adresse.'));
        }
        if ($form->get('adresseCompleteOccupant')->isEmpty() && !$manualAddressEmpty) {
            if ($form->get('adresseOccupant')->isEmpty()) {
                $form->get('adresseOccupant')->addError(new FormError('Veuillez renseigner une adresse.'));
            }
            if ($form->get('cpOccupant')->isEmpty()) {
                $form->get('cpOccupant')->addError(new FormError('Veuillez renseigner un code postal.'));
            }
            if ($form->get('villeOccupant')->isEmpty()) {
                $form->get('villeOccupant')->addError(new FormError('Veuillez renseigner une ville.'));
            }
        }
    }
}
