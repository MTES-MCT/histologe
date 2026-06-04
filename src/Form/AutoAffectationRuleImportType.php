<?php

namespace App\Form;

use App\Form\Type\TerritoryChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * @extends AbstractType<mixed>
 */
class AutoAffectationRuleImportType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('territory', TerritoryChoiceType::class, [
                'required' => false,
                'label' => 'Territoire',
                'placeholder' => 'Sélectionnez un territoire',
                'constraints' => [new NotNull(message: 'Veuillez sélectionner un territoire.')],
            ])
            ->add('csvFile', FileType::class, [
                'label' => 'Fichier CSV',
                'help' => 'Format accepté : .csv — Séparateur : point-virgule (;)',
                'mapped' => false,
                'required' => false,
                'constraints' => [new NotNull(message: 'Veuillez sélectionner un fichier CSV.')],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_token_id' => 'auto_affectation_rule_import',
        ]);
    }
}
