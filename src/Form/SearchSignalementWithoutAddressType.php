<?php

namespace App\Form;

use App\Entity\Enum\SignalementStatus;
use App\Form\Type\TerritoryChoiceType;
use App\Service\ListFilters\SearchSignalementWithoutAddress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class SearchSignalementWithoutAddressType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('territory', TerritoryChoiceType::class);
        $builder->add('statut', EnumType::class, [
            'class' => SignalementStatus::class,
            'label' => 'Statut',
            'choice_label' => static function ($choice) {
                return $choice->label();
            },
            'required' => false,
            'placeholder' => 'Tous les statuts',
        ]);
        $builder->add('isImported', ChoiceType::class, [
            'choices' => [
                'Oui' => true,
                'Non' => false,
            ],
            'required' => false,
            'placeholder' => 'Tous',
            'label' => 'Importé',
        ]);
        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Identifiant (croissant)' => 's.id-ASC',
                'Identifiant (décroissant)' => 's.id-DESC',
                'Référence (A -> Z)' => 's.reference-ASC',
                'Référence (Z -> A)' => 's.reference-DESC',
                'Le plus récent' => 's.createdAt-DESC',
                'Le plus ancien' => 's.createdAt-ASC',
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
            'data_class' => SearchSignalementWithoutAddress::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-signalement-without-address-form', 'class' => 'fr-pt-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
