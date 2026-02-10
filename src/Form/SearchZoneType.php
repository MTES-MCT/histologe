<?php

namespace App\Form;

use App\Entity\Enum\ZoneType;
use App\Form\Type\TerritoryChoiceType;
use App\Service\ListFilters\SearchZone;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchZoneType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryName', SearchType::class, [
            'required' => false,
            'label' => 'Zone',
            'attr' => ['placeholder' => 'Taper le nom d\'une zone'],
        ]);
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $builder->add('territory', TerritoryChoiceType::class);
        }

        $builder->add('type', EnumType::class, [
            'class' => ZoneType::class,
            'choice_label' => function ($choice) {
                return $choice->label();
            },
            'required' => false,
            'placeholder' => 'Tous les types de zone',
            'label' => 'Type de zone',
        ]);

        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Ordre alphabétique (A -> Z)' => 'z.name-ASC',
                'Ordre alphabétique inversé (Z -> A)' => 'z.name-DESC',
                'Ordre croissant' => 'z.id-ASC',
                'Ordre décroissant' => 'z.id-DESC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'z.name-ASC',
        ]);

        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchZone::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-zone-form', 'class' => 'fr-pt-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
