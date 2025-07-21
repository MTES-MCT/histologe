<?php

namespace App\Form\Type;

use App\Repository\TerritoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TerritoryWithNoneChoiceType extends AbstractType
{
    public function __construct(private TerritoryRepository $territoryRepository)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $territories = $this->territoryRepository->findAllList();
        $choices = ['Aucun' => 'none'];
        foreach ($territories as $territory) {
            $choices[$territory->getZip().' - '.$territory->getName()] = $territory->getId();
        }
        $resolver->setDefaults([
            'required' => false,
            'placeholder' => 'Tous les territoires',
            'label' => 'Territoire',
            'choices' => $choices,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
