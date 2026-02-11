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
            'label' => 'Nom du service de secours',
            'help' => 'Sera enregistré comme structure du déclarant.',
            'required' => false,
        ]);
        $builder->add('email', null, [
            'label' => 'E-mail du service de secours',
            'help' => 'Sera enregistré comme e-mail du déclarant.',
            'required' => false,
        ]);
        $builder->add('phone', null, [
            'label' => 'Téléphone du service de secours',
            'help' => 'Sera enregistré comme téléphone du déclarant.',
            'required' => false,
        ]);
        $builder->add('submit', SubmitType::class, [
            'label' => 'Valider',
            'attr' => ['class' => 'fr-btn fr-icon-check-line fr-btn--icon-left'],
            'row_attr' => ['class' => 'fr-text--right'],
        ]);
    }
}
