<?php

namespace App\Form;

use App\Form\Type\TerritoryChoiceType;
use App\Service\ListFilters\SearchAutoAffectationRule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchAutoAffectationRuleType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('territory', TerritoryChoiceType::class)
                ->add('isActive', ChoiceType::class, [
                    'required' => false,
                    'label' => 'Statut',
                    'placeholder' => 'Tous les statuts',
                    'choices' => [
                        'Activé' => true,
                        'Non activé' => false,
                    ],
                ]);

        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Territoire (A -> Z)' => 'aar.territory-ASC',
                'Territoire inversé (Z -> A)' => 'aar.territory-DESC',
                'Type partenaire (A -> Z)' => 'aar.partnerType-ASC',
                'Type partenaire inversé (Z -> A)' => 'aar.partnerType-DESC',
                'Profil déclarant (Z -> A)' => 'aar.profileDeclarant-ASC',
                'Profil déclarant inversé (Z -> A)' => 'aar.profileDeclarant-DESC',
                'Parc (A -> Z)' => 'aar.parc-ASC',
                'Parc inversé (Z -> A)' => 'aar.parc-DESC',
                'Allocataire (A -> Z)' => 'aar.allocataire-ASC',
                'Allocataire inversé (Z -> A)' => 'aar.allocataire-DESC',
                'La plus récente' => 'aar.createdAt-DESC',
                'La plus ancienne' => 'aar.createdAt-ASC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'aar.createdAt-DESC',
        ]);

        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchAutoAffectationRule::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-auto-affectation-rule-form', 'class' => 'fr-pt-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
