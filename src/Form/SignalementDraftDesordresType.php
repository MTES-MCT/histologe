<?php

namespace App\Form;

use App\Entity\DesordreCritere;
use App\Entity\DesordrePrecision;
use App\Entity\Signalement;
use App\Form\Type\SearchCheckboxType;
use App\Repository\DesordreCritereRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignalementDraftDesordresType extends AbstractType
{
    private DesordreCritereRepository $desordreCritereRepository;

    public function __construct(DesordreCritereRepository $desordreCritereRepository)
    {
        $this->desordreCritereRepository = $desordreCritereRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Signalement $signalement */
        $signalement = $builder->getData();
        $details = $signalement->getDetails();

        // Récupération des critères et regroupement par zoneCategorie -> labelCategorie
        // TODO : vérifier s'il peut y avoir des critères archivés pour ne pas les prendre en compte
        $desordreCriteres = $this->desordreCritereRepository->findAll();
        $groupedCriteria = [];

        // Récupérer les précisions sélectionnées
        $signalementPrecisions = $signalement ? $signalement->getDesordrePrecisions()->toArray() : [];
        foreach ($desordreCriteres as $critere) {
            $zone = $critere->getZoneCategorie()->value;
            $labelCategorie = $critere->getLabelCategorie();

            $groupedCriteria[$zone][$labelCategorie][] = $critere;

            if ($critere->getDesordrePrecisions()->count() > 1) {
                $choices = $this->getPrecisionsChoices($critere);
                // Vérifier les précisions déjà sélectionnées
                $selectedValues = [];
                foreach ($signalementPrecisions as $selectedPrecision) {
                    if ($selectedPrecision->getDesordreCritere() === $critere) {
                        $selectedValues[] = $selectedPrecision; // Ajouter les précisions déjà sélectionnées
                    }
                }
                // TODO : si besoin de préciser via champ texte (autre type de nuisible)
                $builder->add('precisions_'.$critere->getId(), ChoiceType::class, [
                    'label' => $critere->getLabelCritere(),
                    'choices' => $choices,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                    'mapped' => false,
                    'data' => $selectedValues,
                ]);
            }
        }

        $builder
            ->add('details', TextareaType::class, [
                'label' => 'Description du problème',
                'help' => 'Saisissez ici la description du problème tel que présenté par le déclarant ainsi que toutes les informations nécessaires au traitement du dossier.',
                'required' => false,
                'mapped' => false,
                'data' => $details,
            ]);

        // Récupérer les critères sélectionnés
        $signalementCriteres = $signalement ? $signalement->getDesordreCriteres()->toArray() : [];
        // Récupérer les critères sélectionnés sous forme d'un tableau d'ID
        $selectedCriteriaIds = array_map(fn($critere) => $critere->getId(), $signalementCriteres);
        foreach ($groupedCriteria as $zone => $categories) {
            foreach ($categories as $labelCategorie => $criteres) {
                $firstCritereId = $criteres[0]->getId();
                // TODO : voir avec Mathilde si on garde la catégorie Type et composition du logement, ou si on la calcule à la volée comme pour le front
                $builder->add("desordres_{$zone}_{$firstCritereId}", SearchCheckboxType::class, [
                    'class' => DesordreCritere::class,
                    'query_builder' => function (DesordreCritereRepository $repo) use ($zone, $labelCategorie) {
                        return $repo->createQueryBuilder('c')
                            ->where('c.zoneCategorie = :zone')
                            ->andWhere('c.labelCategorie = :labelCategorie')
                            ->setParameter('zone', $zone)
                            ->setParameter('labelCategorie', $labelCategorie)
                            ->orderBy('c.labelCritere', 'ASC');
                    },
                    'label' => $labelCategorie, // TODO : pour la catégorie Humidité, les 3 critères ont le même label, à retravailler  pour ajouter la pièce
                    'choice_label' => 'labelCritere',
                    'noselectionlabel' => 'Sélectionner une ou plusieurs options',
                    'nochoiceslabel' => 'Aucun critère disponible',
                    'mapped' => false,
                    'required' => false,
                    'data' => $criteres ? array_filter($criteres, fn($critere) => in_array($critere->getId(), $selectedCriteriaIds)) : [],
                ]);

            }
        }

        $builder
            ->add('forceSave', HiddenType::class, ['mapped' => false])
            ->add('previous', SubmitType::class, [
                'label' => 'Précédent',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-left-line fr-btn--icon-left fr-btn--secondary'],
                'row_attr' => ['class' => 'fr-ml-2w'],
            ])
            ->add('draft', SubmitType::class, [
                'label' => 'Finir plus tard',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-go-forward-line fr-btn--icon-left fr-btn--tertiary-no-outline'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Suivant',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-right-line fr-btn--icon-right'],
                'row_attr' => ['class' => 'fr-ml-2w'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['bo_step_desordres'],
        ]);
    }

    private function getPrecisionsChoices(DesordreCritere $critere): array
    {
        $choices = [];

        /** @var DesordrePrecision $precision */
        foreach ($critere->getDesordrePrecisions() as $precision) {
            $choices[$precision->getLabel()] = $precision;
        }

        return $choices;
    }
}
