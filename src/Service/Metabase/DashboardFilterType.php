<?php

namespace App\Service\Metabase;

use App\Form\Type\TerritoryChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DashboardFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('territory', TerritoryChoiceType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DashboardFilter::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => [
                'id' => 'metabase-dashboard-filter-form',
                'class' => 'fr-p-4v bo-filter-form',
            ],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
