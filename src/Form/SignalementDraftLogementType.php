<?php

namespace App\Form;

use App\Entity\Enum\ChauffageType;
use App\Entity\Enum\EtageType;
use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignalementDraftLogementType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Signalement $signalement */
        $signalement = $builder->getData();

        $appartementEtage = null;
        $appartementAvecFenetres = '';
        if ('appartement' === $signalement->getNatureLogement()) {
            if ($signalement->getTypeCompositionLogement()) {
                if (!empty($signalement->getTypeCompositionLogement()->getTypeLogementAppartementEtage())) {
                    $appartementEtage = EtageType::tryFrom($signalement->getTypeCompositionLogement()->getTypeLogementAppartementEtage());
                // Not used at the moment, for future purpose
                } elseif ('oui' == $signalement->getTypeCompositionLogement()->getTypeLogementRdc()) {
                    $appartementEtage = EtageType::RDC;
                } elseif ('oui' == $signalement->getTypeCompositionLogement()->getTypeLogementDernierEtage()) {
                    $appartementEtage = EtageType::DERNIER_ETAGE;
                } elseif ('oui' == $signalement->getTypeCompositionLogement()->getTypeLogementSousSolSansFenetre()) {
                    $appartementEtage = EtageType::SOUSSOL;
                }

                if (!empty($signalement->getTypeCompositionLogement()->getTypeLogementAppartementAvecFenetres())) {
                    $appartementAvecFenetres = $signalement->getTypeCompositionLogement()->getTypeLogementAppartementAvecFenetres();
                // Not used at the moment, for future purpose
                } elseif ('oui' == $signalement->getTypeCompositionLogement()->getTypeLogementSousCombleSansFenetre()) {
                    $appartementAvecFenetres = 'non';
                } elseif ('oui' == $signalement->getTypeCompositionLogement()->getTypeLogementSousSolSansFenetre()) {
                    $appartementAvecFenetres = 'non';
                }
            }
        }
        $nombreEtages = $signalement->getInformationComplementaire()?->getInformationsComplementairesLogementNombreEtages();
        $anneeConstruction = $signalement->getInformationComplementaire()?->getInformationsComplementairesLogementAnneeConstruction();
        $pieceUnique = $signalement->getTypeCompositionLogement()?->getCompositionLogementPieceUnique();
        $nombrePieces = $signalement->getTypeCompositionLogement()?->getCompositionLogementNbPieces();
        $superficie = $signalement->getTypeCompositionLogement()?->getCompositionLogementSuperficie();
        $hauteur = $signalement->getTypeCompositionLogement()?->getCompositionLogementHauteur();
        $pieceAVivre9m = $signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesPieceAVivre9m() ?? null;

        $cuisine = $signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesCuisine();
        if ('non' === $cuisine && 'oui' === $signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesCuisineCollective()) {
            $cuisine = 'collective';
        }
        $sdb = $signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesSalleDeBain();
        if ('non' === $sdb && 'oui' === $signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesSalleDeBainCollective()) {
            $sdb = 'collective';
        }
        $toilettes = $signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesWc();
        if ('non' === $toilettes && 'oui' === $signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesWcCollective()) {
            $toilettes = 'collective';
        }
        $toilettesCuisineMemePiece = '';
        if ('oui' === $cuisine && 'oui' === $toilettes) {
            $toilettesCuisineMemePiece = $signalement->getTypeCompositionLogement()?->getTypeLogementCommoditesWcCuisine();
        }
        $jsonContent = $signalement->getJsonContent();
        $typeChauffageLabel = $jsonContent['desordres_logement_chauffage'] ?? '';
        $typeChauffage = ChauffageType::tryFrom($typeChauffageLabel);

        $builder
            ->add('natureLogement', ChoiceType::class, [
                'label' => 'Type de logement',
                'choices' => [
                    'Appartement' => 'appartement',
                    'Maison seule' => 'maison',
                    'Autre' => 'autre',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'data' => $signalement->getNatureLogement(),
            ])
            ->add('natureLogementAutre', TextType::class, [
                'label' => 'Précisez le type de logement :',
                'help' => 'Format attendu : 15 caractères maximum',
                'required' => false,
                'mapped' => false,
                'data' => $signalement->getTypeCompositionLogement()->getTypeLogementNatureAutrePrecision(),
            ])
            ->add('appartementEtage', EnumType::class, [
                'label' => 'Localisation de l\'appartement',
                'class' => EtageType::class,
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $appartementEtage,
            ])
            ->add('appartementAvecFenetres', ChoiceType::class, [
                'label' => 'L\'appartement a-t-il des fenêtres ?',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $appartementAvecFenetres,
            ])
            ->add('nombreEtages', NumberType::class, [
                'label' => 'Nombre de niveaux',
                'help' => 'Format attendu : saisir un nombre entier',
                'required' => false,
                'mapped' => false,
                'data' => $nombreEtages,
            ])
            ->add('anneeConstruction', NumberType::class, [
                'label' => 'Année de construction',
                'help' => 'Format attendu : saisir l\'année de construction avec 4 chiffres',
                'required' => false,
                'mapped' => false,
                'data' => $anneeConstruction,
            ])

            ->add('pieceUnique', ChoiceType::class, [
                'label' => 'Composition du logement',
                'choices' => [
                    'Pièce unique' => 'piece_unique',
                    'Plusieurs pièces' => 'plusieurs_pieces',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $pieceUnique,
            ])
            ->add('nombrePieces', NumberType::class, [
                'label' => 'Nombre de pièces à vivre (salon, chambre)',
                'help' => 'Format attendu : saisir un nombre entier',
                'required' => false,
                'mapped' => false,
                'data' => $nombrePieces,
            ])
            ->add('superficie', NumberType::class, [
                'label' => 'Superficie du logement (en m²)',
                'help' => 'La superficie permet de calculer le risque de suroccupation du logement. Format attendu : Saisir un nombre entier',
                'required' => false,
                'mapped' => false,
                'data' => $superficie,
            ])
            ->add('hauteur', ChoiceType::class, [
                'label' => 'Hauteur sous plafond supérieure ou égale à 2m (200 cm)',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $hauteur,
            ])
            ->add('pieceAVivre9m', ChoiceType::class, [
                'label' => 'Au moins une pièce à vivre supérieure ou égale à 9m²',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $pieceAVivre9m,
            ])

            ->add('cuisine', ChoiceType::class, [
                'label' => 'Cuisine ou coin cuisine',
                'choices' => [
                    'Oui' => 'oui',
                    'Accès cuisine collective' => 'collective',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $cuisine,
            ])
            ->add('sdb', ChoiceType::class, [
                'label' => 'SDB, salle d\'eau avec douche ou baignoire',
                'choices' => [
                    'Oui' => 'oui',
                    'Accès SDB / douches collectives' => 'collective',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $sdb,
            ])
            ->add('toilettes', ChoiceType::class, [
                'label' => 'Toilettes (WC)',
                'choices' => [
                    'Oui' => 'oui',
                    'Accès WC collectifs' => 'collective',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $toilettes,
            ])
            ->add('toilettesCuisineMemePiece', ChoiceType::class, [
                'label' => 'Toilettes (WC) et cuisine dans la même pièce',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $toilettesCuisineMemePiece,
            ])
            ->add('typeChauffage', EnumType::class, [
                'label' => 'Type de chauffage',
                'class' => ChauffageType::class,
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'expanded' => false,
                'multiple' => false,
                'required' => false,
                'placeholder' => 'Sélectionner un type de chauffage',
                'mapped' => false,
                'data' => $typeChauffage,
            ])

            ->add('forceSave', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('previous', SubmitType::class, [
                'label' => 'Précédent',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-left-line fr-btn--icon-left fr-btn--secondary', 'data-target' => 'adresse'],
                'row_attr' => ['class' => 'fr-ml-2w'],
            ])
            ->add('draft', SubmitType::class, [
                'label' => 'Finir plus tard',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-go-forward-line fr-btn--icon-left fr-btn--tertiary-no-outline'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Suivant',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-right-line fr-btn--icon-right', 'data-target' => 'situation'],
                'row_attr' => ['class' => 'fr-ml-2w'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['bo_step_logement'],
        ]);
    }
}
