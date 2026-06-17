<?php

namespace App\Form;

use App\Entity\Enum\TypeArrete;
use App\Form\Type\SearchCheckboxEnumType;
use App\Form\Type\TerritoryChoiceType;
use App\Service\ListFilters\SearchArrete;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class SearchArreteType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var SearchArrete $searchArrete */
        $searchArrete = $builder->getData();
        $user = $searchArrete->getUser();

        if ($user->isSuperAdmin() || count($user->getPartnersTerritories()) > 1) {
            $builder->add('territory', TerritoryChoiceType::class);
        }

        $builder->add('autocompleteAddress', TextType::class, [
            'required' => false,
            'label' => 'Adresse',
            'attr' => [
                'autocomplete' => 'off',
                'data-fr-adresse-autocomplete' => 'true',
                'data-autocomplete-query-selector' => '#search-arrete-form .fr-address-arrete-group',
            ],
        ]);
        $builder->add('housenumber', HiddenType::class, [
            'attr' => [
                'data-autocomplete-housenumber' => 'true',
            ],
        ]);
        $builder->add('street', HiddenType::class, [
            'attr' => [
                'data-autocomplete-street' => 'true',
            ],
        ]);
        $builder->add('postCode', HiddenType::class, [
            'attr' => [
                'data-autocomplete-codepostal' => 'true',
            ],
        ]);
        $builder->add('city', HiddenType::class, [
            'attr' => [
                'data-autocomplete-ville' => 'true',
            ],
        ]);
        $builder->add('cityCode', HiddenType::class, [
            'attr' => [
                'data-autocomplete-insee' => 'true',
            ],
        ]);

        $builder->add('typeArretes', SearchCheckboxEnumType::class, [
            'class' => TypeArrete::class,
            'label' => 'Types d\'arrêtés',
            'choices' => TypeArrete::getChoices(),
            'choice_label' => static function (TypeArrete $typeArrete) {
                return $typeArrete->value;
            },
        ]);
        $builder->add('mainLevee', ChoiceType::class, [
            'choices' => [
                'Avec' => true,
                'Sans' => false,
            ],
            'required' => false,
            'placeholder' => 'Tous',
            'label' => 'Avec ou sans main levée',
        ]);

        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Date de l\'arrêté (du plus ancien au plus récent)' => 'a.dateArrete-ASC',
                'Date de l\'arrêté (du plus récent au plus ancien)' => 'a.dateArrete-DESC',
                'Date de l\'import (du plus ancien au plus récent)' => 'a.imported-ASC',
                'Date de l\'import (du plus récent au plus ancien)' => 'a.imported-DESC',
            ],
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'a.label-ASC',
        ]);

        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchArrete::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-arrete-form', 'class' => 'fr-pt-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
