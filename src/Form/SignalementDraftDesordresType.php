<?php

namespace App\Form;

use App\Entity\DesordreCritere;
use App\Entity\DesordrePrecision;
use App\Entity\Signalement;
use App\Form\Type\SearchCheckboxType;
use App\Repository\DesordreCritereRepository;
use App\Utils\TrimHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignalementDraftDesordresType extends AbstractType
{
    private DesordreCritereRepository $desordreCritereRepository;

    public function __construct(DesordreCritereRepository $desordreCritereRepository)
    {
        $this->desordreCritereRepository = $desordreCritereRepository;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Signalement $signalement */
        $signalement = $builder->getData();
        $details = $signalement->getDetails();

        $desordreCriteres = $this->desordreCritereRepository->findAll();
        $groupedCriteria = [];

        $signalementPrecisions = $signalement ? $signalement->getDesordrePrecisions()->toArray() : [];
        foreach ($desordreCriteres as $critere) {
            $zone = $critere->getZoneCategorie()->value;
            $labelCategorie = $critere->getLabelCategorie();

            $groupedCriteria[$zone][$labelCategorie][] = $critere;

            if ($critere->getDesordrePrecisions()->count() > 1) {
                $choices = $this->getPrecisionsChoices($critere);
                $selectedValues = [];
                foreach ($signalementPrecisions as $selectedPrecision) {
                    if ($selectedPrecision->getDesordreCritere() === $critere) {
                        $selectedValues[] = $selectedPrecision;
                    }
                }
                $builder->add('precisions_'.$critere->getId(), ChoiceType::class, [
                    'label' => $critere->getLabelCritere(),
                    'choices' => $choices,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                    'mapped' => false,
                    'data' => $selectedValues,
                    'attr' => [
                        'data-slug-critere' => $critere->getSlugCritere(),
                    ],
                ]);
            }
            $critereSlug = $critere->getSlugCritere();
            if (in_array($critereSlug, [
                'desordres_logement_nuisibles_autres',
                'desordres_batiment_nuisibles_autres',
            ])) {
                $jsonContent = $signalement ? $signalement->getJsonContent() : [];
                $key = $critereSlug;
                $value = isset($jsonContent[$key]) ? $jsonContent[$key] : '';

                $builder->add('precisions_'.$critere->getId().'_'.$critereSlug.'_details_type_nuisibles', TextType::class, [
                    'label' => $critere->getLabelCritere(),
                    'required' => false,
                    'mapped' => false,
                    'data' => $value,
                    'attr' => [
                        'data-slug-critere' => $critere->getSlugCritere(),
                    ],
                ]);

            } elseif ('desordres_logement_lumiere_plafond_trop_bas' === $critereSlug) {
                $jsonContent = $signalement ? $signalement->getJsonContent() : [];
                $suffixes = [
                    '_piece_a_vivre',
                    '_cuisine',
                    '_salle_de_bain',
                    '_toutes_pieces',
                ];

                foreach ($suffixes as $suffix) {
                    $key = $critereSlug.$suffix;
                    $value = isset($jsonContent[$key]) ? $jsonContent[$key] : '';
                    $builder->add('precisions_'.$critere->getId().'_'.$key, TextType::class, [
                        'label' => 'Hauteur (en cm)',
                        'required' => false,
                        'mapped' => false,
                        'data' => $value,
                        'attr' => [
                            'data-slug-critere' => $critere->getSlugCritere(),
                            'placeholder' => 'Ex : 200',
                        ],
                    ]);
                }
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

        $signalementCriteres = $signalement ? $signalement->getDesordreCriteres()->toArray() : [];
        $selectedCriteriaIds = array_map(fn ($critere) => $critere->getId(), $signalementCriteres);
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
                    'label' => $labelCategorie,
                    'choice_label' => function (DesordreCritere $critere) {
                        // Cas particulier des critères ayant un label identique
                        if ('desordres_logement_humidite_cuisine' === $critere->getSlugCritere()) {
                            return 'Le logement est humide et a des traces de moisissures dans la cuisine';
                        } elseif ('desordres_logement_humidite_salle_de_bain' === $critere->getSlugCritere()) {
                            return 'Le logement est humide et a des traces de moisissures dans la salle de bain';
                        } elseif ('desordres_logement_humidite_piece_a_vivre' === $critere->getSlugCritere()) {
                            return 'Le logement est humide et a des traces de moisissures dans une pièce à vivre';
                        }

                        return $critere->getLabelCritere();
                    },
                    'noselectionlabel' => 'Sélectionner une ou plusieurs options',
                    'nochoiceslabel' => 'Aucun critère disponible',
                    'mapped' => false,
                    'required' => false,
                    'data' => array_filter($criteres, fn ($critere) => in_array($critere->getId(), $selectedCriteriaIds)),
                ]);
            }
        }

        $builder
            ->add('forceSave', HiddenType::class, ['mapped' => false])
            ->add('previous', SubmitType::class, [
                'label' => 'Précédent',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-left-line fr-btn--icon-left fr-btn--secondary', 'data-target' => 'coordonnees', 'value' => 'previous'],
                'row_attr' => ['class' => 'fr-ml-2w'],
            ])
            ->add('draft', SubmitType::class, [
                'label' => 'Finir plus tard',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-go-forward-line fr-btn--icon-left fr-btn--tertiary-no-outline', 'value' => 'later'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Suivant',
                'attr' => ['class' => 'fr-btn fr-icon-arrow-right-line fr-btn--icon-right', 'data-target' => 'validation', 'value' => 'next'],
                'row_attr' => ['class' => 'fr-ml-2w'],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();

            $selectedCriteres = [];
            $precisionData = [];
            $commentaires = [];

            foreach ($form->all() as $field) {
                $name = $field->getName();

                if (str_starts_with($name, 'desordres_')) {
                    foreach ($field->getData() ?? [] as $critere) {
                        $selectedCriteres[$critere->getId()] = $critere;
                    }
                }
                if (str_starts_with($name, 'precisions_')) {
                    if (preg_match('/^precisions_(\d+)$/', $name, $matches)) {
                        $critereId = (int) $matches[1];
                        if (!$field->getConfig()->getOption('mapped')) {
                            $precisionData[$critereId] = $field->getData();
                        }
                    }

                    if (str_contains($name, '_details_type_nuisibles')) {
                        if (preg_match('/_(desordres_[a-z_]+_nuisibles_autres)_details_type_nuisibles$/', $name, $matches)) {
                            $slug = $matches[1];
                            $commentaires[$slug] = TrimHelper::safeTrim($field->getData());
                        }
                    }
                }
            }

            foreach ($selectedCriteres as $critereId => $critere) {
                $slug = $critere->getSlugCritere();
                $nbPrecisions = $critere->getDesordrePrecisions()->count();
                $precisions = $precisionData[$critereId] ?? [];
                $commentaire = $commentaires[$slug] ?? null;

                if ($nbPrecisions > 1 && empty($precisions)) {
                    $form->addError(new FormError(''));

                    return;
                }

                if ('desordres_batiment_nuisibles_autres' === $slug) {
                    if ('' === $commentaire) {
                        $form->addError(new FormError(''));

                        return;
                    }
                }

                if ('desordres_logement_nuisibles_autres' === $slug) {
                    if ('' === $commentaire) {
                        $form->addError(new FormError(''));

                        return;
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['bo_step_desordres'],
        ]);
    }

    /**
     * @return array<string, DesordrePrecision>
     */
    private function getPrecisionsChoices(DesordreCritere $critere): array
    {
        $choices = [];

        /** @var DesordrePrecision $precision */
        foreach ($critere->getDesordrePrecisions() as $precision) {
            $label = $precision->getLabel();
            if ('desordres_type_composition_logement_suroccupation_non_allocataire' === $precision->getDesordrePrecisionSlug()) {
                $label .= ' (non-allocataire)';
            } elseif ('desordres_type_composition_logement_suroccupation_allocataire' === $precision->getDesordrePrecisionSlug()) {
                $label .= ' (allocataire)';
            }
            $choices[$label] = $precision;
        }

        ksort($choices);

        return $choices;
    }
}
