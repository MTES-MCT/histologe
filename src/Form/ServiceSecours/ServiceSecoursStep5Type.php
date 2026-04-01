<?php

namespace App\Form\ServiceSecours;

use App\Dto\ServiceSecours\FormServiceSecours;
use App\Dto\ServiceSecours\FormServiceSecoursStep5;
use App\Entity\Enum\AppContext;
use App\Repository\DesordreCritereRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursStep5Type extends AbstractType
{
    public function __construct(private readonly DesordreCritereRepository $desordreCritereRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (PreSetDataEvent $event): void {
            $form = $event->getForm();
            $rootData = $form->getRoot()->getData();
            $isAppartement = 'appartement' === $rootData->step2->natureLogement;

            if ($rootData instanceof FormServiceSecours) {
                if ($isAppartement) {
                    $form->add('autresOccupantsDesordre', ChoiceType::class, [
                        'label' => 'D\'autres occupants de l\'immeuble ont-ils rencontré des désordres ? <span class="text-required">*</span>',
                        'label_html' => true,
                        'required' => false,
                        'expanded' => true,
                        'placeholder' => false,
                        'choices' => [
                            'Oui' => 'oui',
                            'Non' => 'non',
                            'Indéterminé' => 'nsp',
                        ],
                    ]);
                }
            }
        });

        /** @var FormServiceSecoursStep5|null $data */
        $data = $options['data'] ?? null;
        $isAutreChecked = $data instanceof FormServiceSecoursStep5
            && \in_array('desordres_service_secours_autre', $data->desordres, true);

        [$choices, $hints] = $this->getDesordreChoicesAndHints();

        $builder->add('desordres', ChoiceType::class, [
            'choices' => $choices,
            'choice_attr' => static function (?string $slug) use ($hints): array {
                return [
                    'data-dsfr-hint' => $hints[$slug] ?? '',
                ];
            },
            'multiple' => true,
            'expanded' => true,
            'help' => 'Vous pouvez sélectionner plusieurs options.',
            'help_html' => true,
            'label' => '<div class="fr-h5">Désordres <span class="text-required">*</span></div>
                <div class="fr-text--regular fr-mt-1v">
                    Sélectionnez les principaux éléments motivant le signalement.
                </div>',
            'label_html' => true,
        ]);

        $builder->add(
            'desordresAutre',
            TextareaType::class, [
                'label' => 'Veuillez préciser <span class="text-required">*</span> :',
                'label_html' => true,
                'help' => 'Merci de ne transmettre aucune donnée de santé.',
                'required' => false,
                'row_attr' => [
                    'id' => 'desordres-autre-wrapper',
                    'class' => $isAutreChecked ? '' : 'fr-hidden',
                ],
                'attr' => [
                    'maxlength' => 5000,
                ],
            ],
        );

        // $builder->add('autresOccupantsDesordre', ChoiceType::class, [
        //     'label' => 'D\'autres occupants de l\'immeuble ont-ils rencontré des désordres ? <span class="text-required">*</span>',
        //     'label_html' => true,
        //     'required' => false,
        //     'expanded' => true,
        //     'placeholder' => false,
        //     'choices' => [
        //         'Oui' => 'oui',
        //         'Non' => 'non',
        //         'Indéterminé' => 'nsp',
        //     ],
        // ]);

        $builder->add('photos', FileType::class, [
            'label' => 'Ajouter des photos',
            'attr' => [
                'class' => 'fr-btn',
            ],
            'mapped' => false,
            'required' => false,
            'multiple' => true,
            'help' => 'Merci de ne transmettre aucun document comportant des données de santé. Les photos ne doivent pas contenir de visages de personnes ou d\'objets personnels.',
            'help_attr' => [
                'class' => 'fr-hint-text',
            ],
        ]);

        $builder->add('uploadedFiles', CollectionType::class, [
            'entry_type' => HiddenType::class,
            'mapped' => true,
            'required' => false,
            'allow_add' => true,
            'allow_delete' => true,
        ]);
    }

    private function getDesordreChoicesAndHints(): array
    {
        $desordres = $this->desordreCritereRepository->findAllWithPrecisions(AppContext::SERVICE_SECOURS);
        $choices = [];
        $hints = [];
        foreach ($desordres as $desordre) {
            $slug = $desordre->getSlugCritere();
            $choices[$desordre->getLabelCritere()] = $slug;

            $precision = $desordre->getDesordrePrecisions()->first();
            $hints[$slug] = $precision ? $precision->getLabel() : '';
        }

        return [$choices, $hints];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormServiceSecoursStep5::class,
        ]);
    }
}
