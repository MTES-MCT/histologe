<?php

namespace App\Form;

use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\ProcedureType;
use App\Entity\Signalement;
use App\Form\Type\SearchCheckboxEnumType;
use App\Service\Signalement\SignalementProcedureService;
use App\Validator\EmailFormatValidator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @extends AbstractType<mixed>
 */
class CloseSignalementType extends AbstractType
{
    public function __construct(private readonly SignalementProcedureService $signalementProcedureService)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $signalement = $builder->getData();
        $isUsagersNotNotifiable = EmailFormatValidator::isInvalidEmail($signalement->getMailDeclarant()) && EmailFormatValidator::isInvalidEmail($signalement->getMailOccupant());
        $isLogementVacant = $signalement->getIsLogementVacant();

        $builder
            ->add('motifCloture', null, [
                'choices' => MotifCloture::getListForSignalement(),
                'label' => 'Motif de fermeture',
                'choice_label' => static function ($choice) {
                    return $choice->label();
                },
                'choice_attr' => static function ($choice) {
                    if (in_array($choice, MotifCloture::getListNeedTravauxPrecisions(), true)) {
                        return ['data-need-travaux-precisions' => 'true'];
                    }

                    return [];
                },
                'required' => false,
                'placeholder' => 'Sélectionner un motif',
                'data' => null,
            ]);
        $builder->add('travauxMiseEnConformite', null, [
            'label' => 'Les travaux de mise en conformité du logement ont-ils été réalisés ?',
            'choice_label' => static function ($choice) {
                return $choice->label();
            },
            'expanded' => true,
            'placeholder' => false,
            'data' => null,
        ]);
        $builder->add('comCloture', null, [
            'label' => 'Précisions',
            'help' => 'Précisez le contexte de la fermeture du dossier (10 caractères minimum).',
            'required' => false,
            'attr' => [
                'class' => 'editor',
            ],
            'data' => null,
        ]);
        $builder->add('isVisibleForUsager', CheckboxType::class, [
            'label' => 'Usager (occupant, tiers déclarant)',
            'row_attr' => [
                'class' => $isUsagersNotNotifiable ? 'fr-hidden' : '',
            ],
            'required' => false,
            'disabled' => $isUsagersNotNotifiable || $isLogementVacant,
            'mapped' => false,
        ]);
        if ($signalement->getReferenceInjonction()) {
            $builder->add('isVisibleForBailleur', CheckboxType::class, [
                'label' => 'Bailleur',
                'required' => false,
                'mapped' => false,
            ]);
        }
        $builder->add('isVisibleForPartners', CheckboxType::class, [
            'label' => 'Partenaires affectés au dossier',
            'required' => false,
            'mapped' => false,
            'disabled' => true,
            'data' => true,
        ]);
        $builder->add('procedures', SearchCheckboxEnumType::class, [
            'class' => ProcedureType::class,
            'choices' => ProcedureType::getListForClotureSignalement(),
            'choice_label' => static function ($choice) {
                return $choice->label();
            },
            'label' => 'Quelles procédures ont été engagées sur ce dossier ?',
            'help' => 'Si une visite a été renseignée sur le dossier : les conclusions de la visite sont reprises automatiquement. Vous pouvez modifier ces informations si elles ne correspondent pas aux procédures engagées sur ce dossier.',
            'mapped' => false,
            'showSelectionAsTags' => true,
            'data' => $this->signalementProcedureService->getProceduresFromIntervention($signalement),
        ]);
        $builder->add('withoutProcedure', CheckboxType::class, [
            'label' => 'Aucune procédure n’a été engagée sur le dossier',
            'mapped' => false,
            'required' => false,
            // Choix obligatoire et exclusif entre "procedures" et "withoutProcedure" :
            // l'exclusivité (décocher/griser "procedures" quand "withoutProcedure" est coché) est gérée côté front
            // (form_cloture_modal.js), il ne reste qu'à vérifier ici qu'un choix a bien été fait.
            'constraints' => [
                new Assert\Callback(
                    callback: static function (mixed $withoutProcedure, ExecutionContextInterface $context): void {
                        /** @var FormInterface<mixed> $form */
                        $form = $context->getObject();
                        if ($withoutProcedure) {
                            if ($form->getParent()->get('procedures')->getData()) {
                                $context->buildViolation('Sélectionnez soit une ou des procédure(s), soit "Aucune procédure n’a été engagée sur le dossier", mais pas les deux.')
                                    ->addViolation();
                            } else {
                                return;
                            }
                        }
                        if (!$form->getParent()->get('procedures')->getData()) {
                            $context->buildViolation('Sélectionnez au moins une procédure, ou cochez "Aucune procédure n’a été engagée sur le dossier".')
                                ->addViolation();
                        }
                    },
                    groups: ['close_signalement'],
                ),
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
