<?php

namespace App\Form;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Service\ListFilters\SearchClubEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchClubEventType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('isInFuture', ChoiceType::class, [
            'choices' => [
                'Tous' => null,
                'À venir' => true,
                'Passés' => false,
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Date des événements',
        ]);
        $builder->add('queryName', SearchType::class, [
            'required' => false,
            'label' => 'Nom de l\'événement',
            'attr' => ['placeholder' => 'Taper une partie du nom de l\'événement'],
        ]);
        $builder->add('partnerType', EnumType::class, [
            'class' => PartnerType::class,
            'choice_label' => function ($choice) {
                return $choice->label();
            },
            'placeholder' => 'Tous les types de partenaire',
            'required' => false,
            'label' => 'Type de partenaire',
        ]);
        $builder->add('partnerCompetence', EnumType::class, [
            'class' => Qualification::class,
            'choice_label' => function ($choice) {
                return $choice->label();
            },
            'placeholder' => 'Toutes les compétences',
            'required' => false,
            'label' => 'Compétence du partenaire',
        ]);
        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Ordre chronologique (du plus ancien au plus récent)' => 'c.dateEvent-ASC',
                'Ordre chronologique inversé (du plus récent au plus ancien)' => 'c.dateEvent-DESC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'c.dateEvent-ASC',
        ]);
        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchClubEvent::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-club-event-form', 'class' => 'fr-p-5v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
