<?php

namespace App\Form;

use App\Entity\Enum\ServiceSecoursCategory;
use App\Form\Type\TerritoryChoiceType;
use App\Service\ListFilters\SearchServiceSecoursRoute;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class SearchServiceSecoursRouteType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('territory', TerritoryChoiceType::class);
        $builder->add('category', EnumType::class, [
            'class' => ServiceSecoursCategory::class,
            'choice_label' => static fn (ServiceSecoursCategory $choice) => $choice->label(),
            'placeholder' => 'Toutes les catégories',
            'required' => false,
            'label' => 'Catégorie',
        ]);
        $builder->add('queryName', SearchType::class, [
            'required' => false,
            'label' => 'Nom du service de secours',
            'attr' => ['placeholder' => 'Taper une partie du nom du service de secours'],
        ]);
        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Ordre alphabétique (A à Z)' => 'ssr.name-ASC',
                'Ordre alphabétique inversé (Z à A)' => 'ssr.name-DESC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'ssr.name-ASC',
        ]);
        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchServiceSecoursRoute::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-service-secours-route-form', 'class' => 'fr-pt-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
