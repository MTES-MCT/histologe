<?php

namespace App\Form;

use App\Service\ListFilters\SearchDraft;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchDraftType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Date de création (du plus récent au plus ancien)' => 's.id-DESC',
                'Date de création (du plus ancien au plus récent)' => 's.id-ASC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 's.id-DESC',
        ]);

        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchDraft::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-draft-form', 'class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
