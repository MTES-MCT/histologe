<?php

namespace App\Form;

use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignalementDraftSituationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Signalement $signalement */
        $signalement = $builder->getData();

        $bail = $signalement->getTypeCompositionLogement()->getBailDpeBail();
        $dpe = $signalement->getTypeCompositionLogement()->getBailDpeDpe();
        $classeEnergetique = $signalement->getTypeCompositionLogement()->getBailDpeClasseEnergetique();
        $dateDpe = $signalement->getTypeCompositionLogement()->getDesordresLogementChauffageDetailsDpeAnnee();
        $etatDesLieux = $signalement->getTypeCompositionLogement()->getBailDpeEtatDesLieux();
        $dateEntreeLogement = $signalement->getTypeCompositionLogement()->getBailDpeDateEmmenagement() ? new \DateTime($signalement->getTypeCompositionLogement()->getBailDpeDateEmmenagement()) : null;
        $montantLoyer = $signalement->getInformationComplementaire() ? $signalement->getInformationComplementaire()->getInformationsComplementairesLogementMontantLoyer() : null;
        $payementLoyersAJour = $signalement->getInformationComplementaire() ? $signalement->getInformationComplementaire()->getInformationsComplementairesSituationOccupantsLoyersPayes() : '';
        $allocataire = '';
        if ('0' === $signalement->getIsAllocataire()) {
            $allocataire = 'non';
        } elseif (!empty($signalement->getIsAllocataire())) {
            $allocataire = 'oui';
        }
        $caisseAllocation = ('caf' === $signalement->getIsAllocataire() || 'msa' === $signalement->getIsAllocataire()) ? $signalement->getIsAllocataire() : '';
        $dateNaissanceAllocataire = $signalement->getDateNaissanceOccupant();
        $numeroAllocataire = $signalement->getNumAllocataire();
        $typeAllocation = $signalement->getInformationComplementaire() ? $signalement->getInformationComplementaire()->getInformationsComplementairesSituationOccupantsTypeAllocation() : '';
        $montantAllocation = $signalement->getMontantAllocation();
        $accompagnementTravailleurSocial = $signalement->getSituationFoyer() ? $signalement->getSituationFoyer()->getTravailleurSocialAccompagnement() : '';
        $beneficiaireRSA = $signalement->getInformationComplementaire() ? $signalement->getInformationComplementaire()->getInformationsComplementairesSituationOccupantsBeneficiaireRsa() : '';
        $beneficiaireFSL = $signalement->getInformationComplementaire() ? $signalement->getInformationComplementaire()->getInformationsComplementairesSituationOccupantsBeneficiaireFsl() : '';
        $dateProprietaireAverti = $signalement->getProprioAvertiAt();
        $moyenInformationProprietaire = $signalement->getInformationProcedure() ? $signalement->getInformationProcedure()->getInfoProcedureBailMoyen() : '';
        $reponseProprietaire = $signalement->getInformationProcedure() ? $signalement->getInformationProcedure()->getInfoProcedureBailReponse() : '';
        $souhaiteQuitterLogement = $signalement->getSituationFoyer() ? $signalement->getSituationFoyer()->getTravailleurSocialQuitteLogement() : '';
        $preavisDepartDepose = $signalement->getSituationFoyer() ? $signalement->getSituationFoyer()->getTravailleurSocialPreavisDepart() : '';
        $logementAssure = '';
        $assuranceContactee = '';
        $reponseAssurance = '';
        if ($signalement->getInformationProcedure()) {
            if ('oui' === $signalement->getInformationProcedure()->getInfoProcedureAssuranceContactee()) {
                $logementAssure = 'oui';
                $assuranceContactee = 'oui';
            } elseif ('non' === $signalement->getInformationProcedure()->getInfoProcedureAssuranceContactee()) {
                $logementAssure = 'oui';
                $assuranceContactee = 'non';
            } elseif ('pas_assurance_logement' === $signalement->getInformationProcedure()->getInfoProcedureAssuranceContactee()) {
                $logementAssure = 'non';
            }

            $reponseAssurance = $signalement->getInformationProcedure()->getInfoProcedureReponseAssurance();
        }

        $builder
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
            ->add('dpe', ChoiceType::class, [
                'label' => 'Diagnostic performance énergétique (DPE)',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $dpe,
            ])
            ->add('classeEnergetique', ChoiceType::class, [
                'label' => 'Classe énergétique du logement',
                'choices' => [
                    '' => '',
                    'A' => 'A',
                    'B' => 'B',
                    'C' => 'C',
                    'D' => 'D',
                    'E' => 'E',
                    'F' => 'F',
                    'G' => 'G',
                ],
                'expanded' => false,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $classeEnergetique,
            ])
            ->add('dateDpe', ChoiceType::class, [
                'label' => 'Date du DPE',
                'choices' => [
                    'A partir de 2023' => 'post2023',
                    'Avant 2023' => 'before2023',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $dateDpe,
            ])
            ->add('etatDesLieux', ChoiceType::class, [
                'label' => 'Etat des lieux',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $etatDesLieux,
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
            ->add('payementLoyersAJour', ChoiceType::class, [
                'label' => 'Paiement des loyers à jour',
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

            ->add('allocataire', ChoiceType::class, [
                'label' => 'Allocataire',
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
                'label' => 'Date de naissance de l\'allocataire',
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $dateNaissanceAllocataire,
            ])
            ->add('numeroAllocataire', TextType::class, [
                'label' => 'Numéro d\'allocataire / de dossier',
                'help' => 'Format attendu : 25 caractères maximum',
                'required' => false,
                'mapped' => false,
                'data' => $numeroAllocataire,
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
            ->add('accompagnementTravailleurSocial', ChoiceType::class, [
                'label' => 'Accompagnement par travailleur / travailleuse sociale',
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
            ->add('beneficiaireRSA', ChoiceType::class, [
                'label' => 'Bénéficiaire RSA',
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
                'label' => 'Bénéficiaire FSL',
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

            ->add('isProprioAverti', ChoiceType::class, [
                'label' => 'Propriétaire / bailleur informé de la situation',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
            ])
            ->add('dateProprietaireAverti', DateType::class, [
                'label' => 'Date d\'information du propriétaire / bailleur',
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $dateProprietaireAverti,
            ])
            ->add('moyenInformationProprietaire', ChoiceType::class, [
                'label' => 'Moyen d\'information du propriétaire / bailleur',
                'choices' => [
                    '' => '',
                    'Courrier' => 'courrier',
                    'E-mail' => 'email',
                    'Téléphone' => 'telephone',
                    'SMS' => 'sms',
                    'Autre' => 'autre',
                    'Ne sais pas' => 'nsp',
                ],
                'expanded' => false,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $moyenInformationProprietaire,
            ])
            ->add('reponseProprietaire', TextareaType::class, [
                'label' => 'Réponse du bailleur / propriétaire',
                'help' => 'Format attendu : 10 caractères minimum',
                'required' => false,
                'mapped' => false,
                'data' => $reponseProprietaire,
            ])
            ->add('isRelogement', ChoiceType::class, [
                'label' => 'Demande de logement / relogement / mutation',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
            ])
            ->add('souhaiteQuitterLogement', ChoiceType::class, [
                'label' => 'Souhaite quitter le logement',
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
                'label' => 'Préavis de départ déposé',
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

            ->add('logementAssure', ChoiceType::class, [
                'label' => 'Le logement est assuré',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $logementAssure,
            ])
            ->add('assuranceContactee', ChoiceType::class, [
                'label' => 'Assurance contactée',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $assuranceContactee,
            ])
            ->add('reponseAssurance', TextareaType::class, [
                'label' => 'Réponse de l\'assurance',
                'help' => 'Format attendu : 10 caractères minimum',
                'required' => false,
                'mapped' => false,
                'data' => $reponseAssurance,
            ])

            ->add('forceSave', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('previous', SubmitType::class, [
                'label' => 'Précédent',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-left-line fr-btn--icon-left fr-btn--secondary', 'data-target' => 'logement', 'value' => 'previous'],
                'row_attr' => ['class' => 'fr-ml-2w'],
            ])
            ->add('draft', SubmitType::class, [
                'label' => 'Finir plus tard',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-go-forward-line fr-btn--icon-left fr-btn--tertiary-no-outline', 'value' => 'later'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Suivant',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-right-line fr-btn--icon-right', 'data-target' => 'coordonnees', 'value' => 'next'],
                'row_attr' => ['class' => 'fr-ml-2w'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['bo_step_situation'],
        ]);
    }
}
