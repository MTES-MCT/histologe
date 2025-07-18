<?php

namespace App\Form;

use App\Entity\Enum\SignalementStatus;
use App\Form\Type\TerritoryChoiceType;
use App\Service\ListFilters\SearchAffectationWithoutSubscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchAffectationWithoutSubscriptionType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('territory', TerritoryChoiceType::class);
        $builder->add('signalementStatus', EnumType::class, [
            'class' => SignalementStatus::class,
            'label' => 'Statut du signalement',
            'choice_label' => function ($choice) {
                return $choice->label();
            },
            'required' => false,
            'placeholder' => 'Tous les statuts',
        ]);
        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Date de création (plus récente en premier)' => 'a.createdAt-ASC',
                'Date de création (plus ancienne en premier)' => 'a.createdAt-DESC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'a.createdAt-DESC',
        ]);
        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchAffectationWithoutSubscription::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-affectation-form', 'class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
