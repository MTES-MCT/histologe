<?php

namespace App\Form\SignalementeEditFO;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProfileOccupant;
use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<mixed>
 */
class InformationsGeneralesType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Signalement $signalement */
        $signalement = $builder->getData();

        $nbEnfantsDansLogement = $signalement->getTypeCompositionLogement()?->getCompositionLogementNombreEnfants();
        $enfantsDansLogementMoinsSixAns = $signalement->getTypeCompositionLogement()?->getCompositionLogementEnfants();
        $bail = $signalement->getTypeCompositionLogement()?->getBailDpeBail();
        $dpe = $signalement->getTypeCompositionLogement()?->getBailDpeDpe();
        $classeEnergetique = $signalement->getTypeCompositionLogement()?->getBailDpeClasseEnergetique();
        $etatDesLieux = $signalement->getTypeCompositionLogement()?->getBailDpeEtatDesLieux();

        $payementLoyersAJour = $signalement->getInformationComplementaire() ? $signalement->getInformationComplementaire()->getInformationsComplementairesSituationOccupantsLoyersPayes() : '';
        $anneeConstruction = $signalement->getInformationComplementaire()?->getInformationsComplementairesLogementAnneeConstruction();
        $dateEffetBail = $signalement->getInformationComplementaire()?->getInformationsComplementairesSituationBailleurDateEffetBail() ? \DateTime::createFromFormat('Y-m-d', $signalement->getInformationComplementaire()->getInformationsComplementairesSituationBailleurDateEffetBail()) : null;

        $facultatifDateEntree =
            (ProfileDeclarant::LOCATAIRE === $signalement->getProfileDeclarant() || ProfileDeclarant::BAILLEUR_OCCUPANT === $signalement->getProfileDeclarant())
            ? ' <span class="text-required">*</span>'
            : ' (facultatif)';

        $builder
            ->add('dateEntree', DateType::class, [
                'label' => 'Date arrivée'.$facultatifDateEntree,
                'label_html' => true,
                'required' => false,
                'placeholder' => false,
                'constraints' => [
                    new Assert\GreaterThan(
                        value: new \DateTimeImmutable('1900-01-01'),
                        message: 'La date d\'entrée dans le logement doit être postérieure au 1er janvier 1900.',
                    ),
                    new Assert\NotBlank(
                        groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT'],
                        message: 'Veuillez préciser la date d\'entrée dans le logement.',
                    ),
                ],
            ])
            ->add('dateEffetBail', DateType::class, [
                'label' => 'Date d\'effet du bail (facultatif)',
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $dateEffetBail,
            ])
            ->add('nbOccupantsLogement', NumberType::class, [
                'label' => 'Nombre de personnes occupant le logement <span class="text-required">*</span>',
                'label_html' => true,
                'help' => 'Format attendu : saisir un nombre entier',
                'attr' => [
                    'maxlength' => 2,
                ],
                'required' => false,
                'constraints' => [
                    new Assert\NotNull(
                        message: 'Veuillez indiquer le nombre de personnes occupant le logement.',
                    ),
                    new Assert\Regex(
                        pattern: '/^\d+$/',
                        message: 'Veuillez saisir un nombre entier.',
                    ),
                    new Assert\GreaterThan(
                        value: 0,
                        message: 'Veuillez saisir un nombre d\'occupants supérieur à 0.',
                    ),
                    new Assert\LessThan(
                        value: 100,
                        message: 'Veuillez saisir un nombre d\'occupants inférieur à 100.',
                    ),
                ],
                'data' => $signalement->getNbOccupantsLogement(),
            ])
            ->add('nbEnfantsDansLogement', NumberType::class, [
                'label' => 'Dont enfants <span class="text-required">*</span>',
                'label_html' => true,
                'help' => 'Format attendu : saisir un nombre entier',
                'attr' => [
                    'maxlength' => 2,
                ],
                'required' => false,
                'mapped' => false,
                'data' => $nbEnfantsDansLogement,
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Veuillez saisir le nombre d\'enfants.',
                    ),
                    new Assert\Regex(
                        pattern: '/^\d+$/',
                        message: 'Veuillez saisir un nombre entier.',
                    ),
                    new Assert\LessThan(
                        value: 100,
                        message: 'Veuillez saisir un nombre d\'enfants inférieur à 100.',
                    ),
                ],
            ])
            ->add('enfantsDansLogementMoinsSixAns', ChoiceType::class, [
                'label' => 'Enfants de 6 ans ou moins (obligatoire si il y a des enfants dans le logement)',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                    'Je ne sais pas' => 'nsp',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $enfantsDansLogementMoinsSixAns,
            ])
            ->add('autreSituationVulnerabilite', TextareaType::class, [
                'label' => 'Autre situation de vulnérabilité (facultatif)',
                'help' => 'Précisez toute autre situation de vulnérabilité (personne âgée, etc.). Ne saisissez aucune donnée de santé.',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->add('dpe', ChoiceType::class, [
                'label' => 'Diagnostic performance énergétique (DPE) <span class="text-required">*</span>',
                'label_html' => true,
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                    'Je ne sais pas' => 'nsp',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $dpe,
                'constraints' => [
                    new Assert\NotNull(
                        message: 'Veuillez renseigner la présence d\'un DPE.',
                    ),
                ],
            ])
            ->add('classeEnergetique', ChoiceType::class, [
                'label' => 'Classe énergétique du logement (obligatoire si DPE présent)',
                'choices' => [
                    'A' => 'A',
                    'B' => 'B',
                    'C' => 'C',
                    'D' => 'D',
                    'E' => 'E',
                    'F' => 'F',
                    'G' => 'G',
                    'Je ne sais pas' => 'nsp',
                ],
                'expanded' => false,
                'multiple' => false,
                'required' => false,
                'placeholder' => 'Sélectionnez la classe énergétique',
                'mapped' => false,
                'data' => $classeEnergetique,
            ])
            ->add('numeroInvariant', TextType::class, [
                'label' => 'Invariant fiscal (facultatif)',
                'help' => 'Format attendu : 12 caractères maximum',
                'required' => false,
                'data' => $signalement->getNumeroInvariant(),
                'attr' => [
                    'maxlength' => 12,
                ],
            ])
            ->add('loyer', NumberType::class, [
                'label' => 'Montant du loyer (facultatif)',
                'required' => false,
            ])
            ->add('payementLoyersAJour', ChoiceType::class, [
                'label' => 'Paiement des loyers à jour (facultatif)',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $payementLoyersAJour,
            ])
            ->add('anneeConstruction', NumberType::class, [
                'label' => 'Année de construction (facultatif)',
                'help' => 'Format attendu : saisir l\'année de construction avec 4 chiffres',
                'required' => false,
                'mapped' => false,
                'data' => $anneeConstruction,
                'attr' => [
                    'maxlength' => 4,
                ],
                'constraints' => [
                    new Assert\Length(
                        exactly: 4,
                        exactMessage: 'L\'année de construction doit comporter {{ limit }} caractères.',
                    ),
                ],
            ]);
        // Pas de contrat de location (bail) ni d'état des lieux lorsque l'occupant est propriétaire du logement
        if (!self::isBailleurOccupant($signalement)) {
            $builder->add('bail', ChoiceType::class, [
                'label' => 'Contrat de location (bail) <span class="text-required">*</span>',
                'label_html' => true,
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                    'Je ne sais pas' => 'nsp',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $bail,
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Veuillez indiquer si un contrat de location (bail) existe.',
                    ),
                ],
            ]);
            $builder->add('etatDesLieux', ChoiceType::class, [
                'label' => 'Etat des lieux <span class="text-required">*</span>',
                'label_html' => true,
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                    'Je ne sais pas' => 'nsp',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $etatDesLieux,
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Veuillez indiquer si un état des lieux a été réalisé.',
                    ),
                ],
            ]);
        }
        if ('appartement' === $signalement->getNatureLogement()) {
            $builder->add('autresOccupantsDesordre', ChoiceType::class, [
                'label' => 'D\'autres occupants de l\'immeuble ont rencontré des désordres (facultatif)',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                    'Je ne sais pas' => 'nsp',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
            ]);
        }
        $builder->add('save', SubmitType::class, [
            'label' => 'Enregistrer',
            'attr' => [
                'class' => 'fr-btn--primary',
            ],
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event) {
            $form = $event->getForm();
            $nbEnfantsDansLogement = $form->get('nbEnfantsDansLogement')->getData();
            $enfantsDansLogementMoinsSixAns = $form->get('enfantsDansLogementMoinsSixAns')->getData();
            if ($nbEnfantsDansLogement > 0 && null === $enfantsDansLogementMoinsSixAns) {
                $form->get('enfantsDansLogementMoinsSixAns')->addError(new FormError('Veuillez indiquer si des enfants de 6 ans ou moins vivent dans le logement.'));
            }

            $dpe = $form->get('dpe')->getData();
            $classeEnergetique = $form->get('classeEnergetique')->getData();
            if ('oui' === $dpe && null === $classeEnergetique) {
                $form->get('classeEnergetique')->addError(new FormError('Veuillez indiquer la classe énergétique du logement si un DPE est présent.'));
            }
        });
    }

    public static function isBailleurOccupant(Signalement $signalement): bool
    {
        return ProfileDeclarant::BAILLEUR_OCCUPANT === $signalement->getProfileDeclarant()
            || ProfileOccupant::BAILLEUR_OCCUPANT === $signalement->getProfileOccupant();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Signalement::class,
            'validation_groups' => static function (FormInterface $form): array {
                $groups = ['Default'];

                $data = $form->getData();
                if ($data instanceof Signalement && null !== $data->getProfileDeclarant()) {
                    $groups[] = $data->getProfileDeclarant()->name;
                }

                return $groups;
            },
        ]);
    }
}
