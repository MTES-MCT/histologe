<?php

namespace App\Form\SignalementeEditFO;

use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UsagerSituationFoyerType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Signalement $signalement */
        $signalement = $builder->getData();

        $isLogementSocial = null;
        $isLogementSocialChoices = [
            'Oui' => true,
            'Non' => false,
        ];
        if (null === $signalement->getIsLogementSocial()) {
            $isLogementSocial = 'nsp';
            // On ajoute le choix "Ne sais pas" uniquement si la valeur est nulle, pour éviter de proposer ce choix si l'utilisateur a déjà renseigné une valeur
            $isLogementSocialChoices['Je ne sais pas'] = 'nsp';
        } else {
            $isLogementSocial = $signalement->getIsLogementSocial();
        }

        $allocataire = '';
        if ('0' === $signalement->getIsAllocataire()) {
            $allocataire = 'non';
        } elseif (!empty($signalement->getIsAllocataire())) {
            $allocataire = 'oui';
        }
        $caisseAllocation = ('caf' === $signalement->getIsAllocataire() || 'msa' === $signalement->getIsAllocataire()) ? $signalement->getIsAllocataire() : '';

        $typeAllocation = $signalement->getInformationComplementaire() ? $signalement->getInformationComplementaire()->getInformationsComplementairesSituationOccupantsTypeAllocation() : '';
        $montantAllocation = $signalement->getMontantAllocation();
        if (empty($montantAllocation)) {
            $montantAllocation = $signalement->getSituationFoyer() ? $signalement->getSituationFoyer()->getLogementSocialMontantAllocation() : null;
        }

        $souhaiteQuitterLogement = $signalement->getSituationFoyer() ? $signalement->getSituationFoyer()->getTravailleurSocialQuitteLogement() : '';
        $preavisDepartDepose = $signalement->getSituationFoyer() ? $signalement->getSituationFoyer()->getTravailleurSocialPreavisDepart() : '';
        $accompagnementTravailleurSocial = $signalement->getSituationFoyer() ? $signalement->getSituationFoyer()->getTravailleurSocialAccompagnement() : '';
        $accompagnementTravailleurSocialNomStructure = $signalement->getSituationFoyer() ? $signalement->getSituationFoyer()->getTravailleurSocialAccompagnementNomStructure() : '';

        $beneficiaireRSA = $signalement->getInformationComplementaire() ? $signalement->getInformationComplementaire()->getInformationsComplementairesSituationOccupantsBeneficiaireRsa() : '';
        $beneficiaireFSL = $signalement->getInformationComplementaire() ? $signalement->getInformationComplementaire()->getInformationsComplementairesSituationOccupantsBeneficiaireFsl() : '';
        $revenuFiscal = $signalement->getInformationComplementaire() ? $signalement->getInformationComplementaire()->getInformationsComplementairesSituationOccupantsRevenuFiscal() : '';

        $departApresTravaux = $signalement->getInformationProcedure() ? $signalement->getInformationProcedure()->getInfoProcedureDepartApresTravaux() : '';

        $builder
            ->add('isLogementSocial', ChoiceType::class, [
                'label' => 'Est-ce qu\'il s\'agit d\'un logement social ?',
                'choices' => $isLogementSocialChoices,
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $isLogementSocial,
                'constraints' => [
                    new Assert\NotNull(
                        message: 'Veuillez déterminer s\'il s\'agit d\'un logement social.',
                    ),
                ],
            ])
            ->add('isRelogement', ChoiceType::class, [
                'label' => 'Est-ce qu\'une demande de relogement a été faite ?',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'constraints' => [
                    new Assert\NotBlank(
                        groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT'],
                        message: 'Veuillez préciser si une demande de relogement a été faite.',
                    ),
                ],
            ])
            ->add('allocataire', ChoiceType::class, [
                'label' => 'Est-ce que l\'occupant est allocataire ?',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $allocataire,
            ])
            ->add('caisseAllocation', ChoiceType::class, [
                'label' => 'Caisse d\'allocation',
                'choices' => [
                    'CAF' => 'caf',
                    'MSA' => 'msa',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $caisseAllocation,
            ])
            ->add('dateNaissanceAllocataire', DateType::class, [
                'label' => 'Date de naissance de la personne qui occupe le logement',
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $signalement->getDateNaissanceOccupant(),
            ])
            ->add('numAllocataire', TextType::class, [
                'label' => 'Numéro d\'allocataire / de dossier',
                'help' => 'Format attendu : 25 caractères maximum',
                'required' => false,
            ])
            ->add('typeAllocation', ChoiceType::class, [
                'label' => 'Type d\'allocation',
                'choices' => [
                    '' => '',
                    'ALS' => 'als',
                    'ALF' => 'alf',
                    'APL' => 'apl',
                ],
                'expanded' => false,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $typeAllocation,
            ])
            ->add('montantAllocation', TextType::class, [
                'label' => 'Montant de l\'allocation',
                'help' => 'Format attendu : saisir un nombre entier',
                'required' => false,
                'mapped' => false,
                'data' => $montantAllocation,
            ])
            ->add('souhaiteQuitterLogement', ChoiceType::class, [
                'label' => 'Est-ce que le foyer souhaite quitter le logement ?',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $souhaiteQuitterLogement,
            ])
            ->add('preavisDepartDepose', ChoiceType::class, [
                'label' => 'Est-ce qu\'un préavis de départ a été déposé ?',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $preavisDepartDepose,
            ])
            ->add('accompagnementTravailleurSocial', ChoiceType::class, [
                'label' => 'Est-ce que le foyer est accompagné par un ou une travailleuse sociale ?',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $accompagnementTravailleurSocial,
            ])
            ->add('accompagnementTravailleurSocialNomStructure', TextType::class, [
                'label' => 'Nom de la structure d\'accompagnement',
                'required' => false,
                'mapped' => false,
                'data' => $accompagnementTravailleurSocialNomStructure,
            ])
            ->add('beneficiaireRSA', ChoiceType::class, [
                'label' => 'Est-ce que le foyer est bénéficiaire du RSA ?',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $beneficiaireRSA,
            ])
            ->add('beneficiaireFSL', ChoiceType::class, [
                'label' => 'Est-ce que le foyer est bénéficiaire du FSL ?',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $beneficiaireFSL,
            ])
            ->add('revenuFiscal', TextType::class, [
                'label' => 'Revenu fiscal de référence',
                'required' => false,
                'mapped' => false,
                'data' => $revenuFiscal,
            ])
            ->add('departApresTravaux', ChoiceType::class, [
                'label' => 'Est-ce que le foyer souhaite rester dans le logement si des travaux sont faits ?',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $departApresTravaux,
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
