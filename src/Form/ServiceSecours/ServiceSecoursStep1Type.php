<?php

namespace App\Form\ServiceSecours;

use App\Dto\ServiceSecours\ServiceSecoursStep1;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursStep1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('matriculeDeclarant', null, [
            'label' => 'Matricule <span class="text-required">*</span>',
            'label_html' => true,
            'required' => false,
        ]); // obligatoire
        $builder->add('nomDeclarant', null, ['label' => 'Nom']);
        $builder->add('origineMission', null, ['label' => 'Engin du BMPM / SDIS à l\'origine du signalement']);
        $builder->add('dateMission', DateType::class, ['label' => 'Date de la mission', 'help' => 'format attendu : JJ/MM/AAAA', 'required' => false]);
        $builder->add('ordreMission', null, ['label' => 'Ordre de mission']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServiceSecoursStep1::class,
        ]);
    }
}
