<?php

namespace App\Form;

use App\Service\ListFilters\SearchServiceSecoursRoute;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchServiceSecoursRouteType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryName', SearchType::class, [
            'required' => false,
            'label' => 'Nom de l\'événement',
            'attr' => ['placeholder' => 'Taper une partie du nom de l\'événement'],
        ]);
        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Ordre alpahbétique (A à Z)' => 'ssr.name-ASC',
                'Ordre alpahbétique inversé (Z à A)' => 'ssr.name-DESC',
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
            'attr' => ['id' => 'search-service-secours-route-form', 'class' => 'fr-p-5v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
