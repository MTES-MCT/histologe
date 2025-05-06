<?php

namespace App\Form;

use App\Entity\Territory;
use App\Service\ListFilters\SearchArchivedSignalement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchArchivedSignalementType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryReference', SearchType::class, [
            'required' => false,
            'label' => 'Référence de signalement',
            'attr' => ['placeholder' => 'Taper la référence'],
        ]);
        $builder->add('territory', EntityType::class, [
            'class' => Territory::class,
            'choice_label' => function (Territory $territory) {
                return $territory->getZip().' - '.$territory->getName();
            },
            'required' => false,
            'placeholder' => 'Tous les territoires',
            'label' => 'Territoire',
        ]);

        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Ordre alphabétique (A -> Z)' => 's.nomOccupant-ASC',
                'Ordre alphabétique inversé (Z -> A)' => 's.nomOccupant-DESC',
                'Ordre croissant' => 's.reference-ASC',
                'Ordre décroissant' => 's.reference-DESC',
                'Le plus récent' => 's.createdAt-DESC',
                'Le plus ancien' => 's.createdAt-ASC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 's.createdAt-ASC',
        ]);

        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchArchivedSignalement::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-archived-signalement-form', 'class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
