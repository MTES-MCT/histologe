<?php

namespace App\Form;

use App\Form\Type\TerritoryChoiceType;
use App\Service\ListFilters\SearchBailleur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchBailleurType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryName', SearchType::class, [
            'required' => false,
            'label' => 'Bailleur',
            'attr' => ['placeholder' => 'Taper le nom ou une partie du nom du bailleur'],
        ]);
        $builder->add('territory', TerritoryChoiceType::class);
        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Ordre alphabétique (A -> Z)' => 'b.name-ASC',
                'Ordre alphabétique inversé (Z -> A)' => 'b.name-DESC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'b.name-ASC',
        ]);
        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchBailleur::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-bailleur-form', 'class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
