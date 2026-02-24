<?php

namespace App\Form\SignalementeEditFO;

use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

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

        $builder->add('dateEntree', DateType::class, [
            'label' => 'Date arrivée :',
            'required' => false,
            'placeholder' => false,
            'data' => $signalement->getDateEntree(),
        ])
            ->add('dateEffetBail', DateType::class, [
                'label' => 'Date d\'effet du bail',
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $dateEffetBail,
            ])
            ->add('nbOccupantsLogement', NumberType::class, [
                'label' => 'Nombre de personnes occupant le logement',
                'help' => 'Format attendu : saisir un nombre entier',
                'required' => false,
                'constraints' => [
                    new Assert\Regex(
                        pattern: '/^\d+$/',
                        message: 'Veuillez saisir un nombre entier.',
                    ),
                ],
                'data' => $signalement->getNbOccupantsLogement(),
            ])
            ->add('nbEnfantsDansLogement', NumberType::class, [
                'label' => 'Dont enfants',
                'help' => 'Format attendu : saisir un nombre entier',
                'required' => false,
                'mapped' => false,
                'data' => $nbEnfantsDansLogement,
                'constraints' => [
                    new Assert\Regex(
                        pattern: '/^\d+$/',
                        message: 'Veuillez saisir un nombre entier.',
                    ),
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
            ->add('numeroInvariant', TextType::class, [
                'label' => 'Invariant fiscal',
                'help' => 'Format attendu : 255 caractères maximum',
                'required' => false,
                'data' => $signalement->getNumeroInvariant(),
            ])
            ->add('loyer', NumberType::class, [
                'label' => 'Montant du loyer',
                'required' => false,
                'data' => $signalement->getLoyer(),
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
            ->add('anneeConstruction', NumberType::class, [
                'label' => 'Année de construction',
                'help' => 'Format attendu : saisir l\'année de construction avec 4 chiffres',
                'required' => false,
                'mapped' => false,
                'data' => $anneeConstruction,
            ]);
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
        ]);
    }
}
