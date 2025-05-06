<?php

namespace App\Form;

use App\Service\ListFilters\SearchNotification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchNotificationType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Date (la plus récente)' => 's.createdAt-DESC',
                'Date (la plus ancienne)' => 's.createdAt-ASC',
                'Référence (ordre croissant)' => 'si.reference-ASC',
                'Référence (ordre décroissant)' => 'si.reference-DESC',
                'Nom de l\'auteur (A -> Z)' => 'cb.nom-ASC',
                'Nom de l\'auteur (Z -> A)' => 'cb.nom-DESC',
                'Commune (A -> Z)' => 'si.villeOccupant-ASC',
                'Commune (Z -> A)' => 'si.villeOccupant-DESC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 's.createdAt-DESC',
        ]);

        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchNotification::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-notification-form', 'class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
