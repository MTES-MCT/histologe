<?php

namespace App\Form\ServiceSecours;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Flow\ButtonFlowInterface;
use Symfony\Component\Form\Flow\FormFlowCursor;
use Symfony\Component\Form\Flow\FormFlowInterface;
use Symfony\Component\Form\Flow\Type\FinishFlowType;
use Symfony\Component\Form\Flow\Type\NextFlowType;
use Symfony\Component\Form\Flow\Type\PreviousFlowType;
use Symfony\Component\Form\Flow\Type\ResetFlowType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursNavigatorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reset', ResetFlowType::class, [
                'label' => 'Annuler',
                'include_if' => static fn (FormFlowCursor $cursor) => 'step1' !== $cursor->getCurrentStep(),
                'attr' => ['class' => 'fr-btn fr-btn--tertiary-no-outline fr-icon-close-line'],
            ])
            ->add('previous', PreviousFlowType::class, [
                'label' => 'Précédent',
                'include_if' => static fn (FormFlowCursor $cursor) => !$cursor->isFirstStep(),
                'attr' => ['class' => 'fr-btn--secondary fr-btn--icon-left fr-icon-arrow-left-line'],
                'clear_submission' => false,
                'validation_groups' => false,
            ])
            ->add('next', NextFlowType::class, [
                'label' => 'Suivant',
                'include_if' => static fn (FormFlowCursor $cursor) => !$cursor->isLastStep() && 'step5' !== $cursor->getCurrentStep(),
                'attr' => ['class' => 'fr-btn--icon-right fr-icon-arrow-right-line'],
            ])
            ->add('nextReview', NextFlowType::class, [
                'label' => 'Vérifier ma saisie',
                'include_if' => static fn (FormFlowCursor $cursor) => 'step5' === $cursor->getCurrentStep(),
                'attr' => ['class' => 'fr-btn--icon-right fr-icon-arrow-right-line'],
            ])
            ->add('finish', FinishFlowType::class, [
                'label' => 'Valider le signalement',
                'include_if' => static fn (FormFlowCursor $cursor) => $cursor->isLastStep(),
            ])
        ;

        $builder
            ->add('editStep1', PreviousFlowType::class, $this->createEditButtonOptions(
                'step1',
                'Modifier les coordonnées',
            ))
            ->add('editStep2', PreviousFlowType::class, $this->createEditButtonOptions(
                'step2',
                'Modifier les infos sur le logement',
            ))
            ->add('editStep3', PreviousFlowType::class, $this->createEditButtonOptions(
                'step3',
                'Modifier l\'occupation du logement',
            ))
            ->add('editStep4', PreviousFlowType::class, $this->createEditButtonOptions(
                'step4',
                'Modifier les informations propriétaire / syndic',
            ))
            ->add('editStep5', PreviousFlowType::class, $this->createEditButtonOptions(
                'step5',
                'Modifier les désordres',
            ));
    }

    private function createEditButtonOptions(string $step, string $label): array
    {
        return [
            'label' => '<span class="fr-hidden fr-unhidden-md">Modifier</span>',
            'label_html' => true,
            'include_if' => static fn (FormFlowCursor $cursor): bool => $cursor->isLastStep(),
            'validation_groups' => false,
            'attr' => [
                'class' => 'fr-btn fr-btn--sm fr-btn--tertiary fr-icon-edit-line fr-btn--icon-left',
                'aria-label' => $label,
                'title' => $label,
            ],
            'handler' => static function (
                mixed $_data, // NOSONAR - unused, must respect the handler signature
                ButtonFlowInterface $_button, // NOSONAR - unused, must respect the handler signature
                FormFlowInterface $flow,
            ) use ($step): void {
                $flow->movePrevious($step);
            },
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'mapped' => false,
        ]);
    }
}
