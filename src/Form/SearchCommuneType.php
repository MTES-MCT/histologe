<?php

namespace App\Form;

use App\Entity\Epci;
use App\Form\Type\TerritoryChoiceType;
use App\Service\ListFilters\SearchCommune;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchCommuneType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryName', SearchType::class, [
            'required' => false,
            'label' => 'Commune',
            'attr' => ['placeholder' => 'Taper le nom ou une partie du nom de la commune'],
        ]);
        $builder->add('territory', TerritoryChoiceType::class);
        $builder->add('epci', EntityType::class, [
            'class' => Epci::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('e')->orderBy('e.nom', 'ASC');
            },
            'choice_label' => 'nom',
            'required' => false,
            'label' => 'EPCI',
            'placeholder' => 'Tous les EPCI',
        ]);
        $builder->add('codePostal', null, [
            'required' => false,
            'label' => 'Code postal',
            'attr' => ['placeholder' => 'Taper le code postal'],
        ]);
        $builder->add('codeInsee', null, [
            'required' => false,
            'label' => 'Code INSEE',
            'attr' => ['placeholder' => 'Taper le code INSEE'],
        ]);
        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Ordre alphabétique (A -> Z)' => 'c.nom-ASC',
                'Ordre alphabétique inversé (Z -> A)' => 'c.nom-DESC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'c.nom-ASC',
        ]);
        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchCommune::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-commune-form', 'class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
