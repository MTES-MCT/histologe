<?php

namespace App\Form\Type;

use App\Entity\Territory;
use App\Repository\TerritoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TerritoryChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Territory::class,
            'choice_label' => fn (Territory $territory) => $territory->getZip().' - '.$territory->getName(),
            'placeholder' => 'Tous les territoires',
            'required' => false,
            'label' => 'Territoire',
            'selected_territory' => null,
            'query_builder' => fn (
                TerritoryRepository $territoryRepository,
            ) => $territoryRepository->createQueryBuilder('t')->andWhere('t.isActive = 1')->orderBy('t.id', 'ASC'),
            'attr' => [],
            'row_attr' => [],
        ]);
        $resolver->setNormalizer('data', function ($options, $value) {
            return $options['selected_territory'] ?? $value;
        });
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
