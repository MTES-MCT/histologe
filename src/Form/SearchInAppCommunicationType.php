<?php

namespace App\Form;

use App\Controller\Back\ConfigInAppCommunicationController;
use App\Service\ListFilters\SearchInAppCommunication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class SearchInAppCommunicationType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryTitleOrDescription', SearchType::class, [
            'required' => false,
            'label' => 'Titre ou description',
            'attr' => ['placeholder' => 'Taper une partie du titre ou de la description'],
        ]);
        $builder->add('communicationType', ChoiceType::class, [
            'choices' => array_flip(ConfigInAppCommunicationController::COMMUNICATION_TYPES),
            'required' => false,
            'placeholder' => 'Tous les types',
            'label' => 'Type de communication',
        ]);
        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Ordre chronologique inversé (du plus récent au plus ancien)' => 'i.id-DESC',
                'Ordre chronologique (du plus ancien au plus récent)' => 'i.id-ASC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'i.id-ASC',
        ]);
        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchInAppCommunication::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-in-app-communication-form', 'class' => 'fr-pt-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
