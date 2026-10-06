<?php

namespace App\Form;

use App\Entity\Enum\ProcedureCategory;
use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\SignalementProcedure;
use App\Form\Type\SearchCheckboxEnumType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<mixed>
 */
class ProcedureEngageeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $signalement = $builder->getData();
        $commentaire = null;
        if ($signalement->getSignalementProcedures(ProcedureCategory::PROCEDURE_ENGAGEE)->count() > 0) {
            $lastSuivi = $signalement->getSuivisWithCategory(SuiviCategory::ADD_OR_EDIT_PROCEDURE_ENGAGEE)->last();
            $commentaire = $lastSuivi ? ($lastSuivi->getOriginalData()['commentaire']) : null;
        }

        $builder->add('procedureEngagees', SearchCheckboxEnumType::class, [
            'class' => ProcedureType::class,
            'choices' => ProcedureType::getListForClotureSignalement(),
            'choice_label' => static function ($choice) {
                return $choice->label();
            },
            'label' => 'Quelles procédures devraient être engagées sur le dossier ?',
            'mapped' => false,
            'showSelectionAsTags' => true,
            'data' => array_map(
                static fn (SignalementProcedure $signalementProcedure): ProcedureType => $signalementProcedure->getProcedureType(),
                $signalement->getSignalementProcedures(ProcedureCategory::PROCEDURE_ENGAGEE)->toArray(),
            ),
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ]);
        $builder->add('commentaire', TextareaType::class, [
            'label' => 'Commentaire',
            'help' => 'Précisez le contexte et donnez des informations sur les procédures.',
            'attr' => [
                'class' => 'editor',
            ],
            'required' => false,
            'mapped' => false,
            'data' => $commentaire,
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(min: 16, minMessage: 'Le commentaire doit contenir au moins {{ limit }} caractères.'),
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
