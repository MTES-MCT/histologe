<?php

namespace App\Form;

use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoordonneesAgenceType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Signalement $signalement */
        $signalement = $builder->getData();
        $adresseCompleteAgence = mb_trim($signalement->getAdresseAgence().' '.$signalement->getCodePostalAgence().' '.$signalement->getVilleAgence());
        $builder
            ->add('denominationAgence', null, [
                'label' => 'Dénomination',
                'required' => false,
            ])
            ->add('nomAgence', null, [
                'label' => 'Nom de famille',
                'help' => 'Saisissez le nom du ou de la gestionnaire du logement',
                'required' => false,
            ])
            ->add('prenomAgence', null, [
                'label' => 'Prénom',
                'help' => 'Saisissez le prénom du ou de la gestionnaire du logement',
                'required' => false,
            ])
            ->add('mailAgence', null, [
                'label' => 'Adresse e-mail',
                'help' => 'Format attendu : nom@domaine.fr',
                'required' => false,
            ])
            ->add('telAgence', null, [
                'label' => 'Numéro de téléphone de l\'agence',
                'required' => false,
            ])
            ->add('telAgenceSecondaire', null, [
                'label' => 'Numéro de téléphone secondaire de l\'agence',
                'required' => false,
            ])
            ->add('adresseCompleteAgence', null, [
                'label' => false,
                'help' => 'Format attendu : Tapez l\'adresse puis sélectionnez-la dans la liste. Si elle n\'apparaît pas, cliquez sur saisir une adresse manuellement.',
                'mapped' => false,
                'data' => $adresseCompleteAgence,
                'attr' => [
                    'autocomplete' => 'off',
                    'data-fr-adresse-autocomplete' => 'true',
                    'data-autocomplete-query-selector' => '#form-usager-complete-dossier .fr-address-agence-group',
                    'data-suffix' => 'agence',
                ],
            ])
            ->add('adresseAgence', null, [
                'label' => 'Numéro et voie',
                'attr' => [
                    'class' => 'manual-address manual-address-input',
                    'data-autocomplete-addresse-agence' => 'true',
                ],
                'empty_data' => '',
            ])
            ->add('codePostalAgence', null, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => [
                    'class' => 'manual-address',
                    'data-autocomplete-codepostal-agence' => 'true',
                ],
                'empty_data' => '',
            ])
            ->add('villeAgence', null, [
                'label' => 'Ville',
                'required' => false,
                'attr' => [
                    'class' => 'manual-address',
                    'data-autocomplete-ville-agence' => 'true',
                ],
                'empty_data' => '',
            ])
            ->add('save', SubmitType::class, [
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
        ]);
    }
}
