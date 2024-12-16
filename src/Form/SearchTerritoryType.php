<?php

namespace App\Form;

use App\Service\SearchTerritory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchTerritoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryName', SearchType::class, [
            'required' => false,
            'label' => false,
            'attr' => ['placeholder' => 'Taper le code ou le nom d\'un territoire'],
        ]);
        $builder->add('isActive', ChoiceType::class, [
            'required' => false,
            'label' => false,
            'placeholder' => 'Statut',
            'choices' => [
                'Activé' => true,
                'Non activé' => false,
            ],
        ]);

        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchTerritory::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-territory-form', 'class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
