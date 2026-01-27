<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ServiceSecoursRouteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', null, [
            'label' => 'Type de service de secours',
            'help' => '255 caractères maximum.',
            'required' => false,
        ]);
        $builder->add('submit', SubmitType::class, [
            'label' => 'Valider',
            'attr' => ['class' => 'fr-btn fr-icon-check-line fr-btn--icon-left'],
            'row_attr' => ['class' => 'fr-text--right'],
        ]);
    }
}
