<?php

namespace App\Form\ServiceSecours;

use App\Dto\ServiceSecours\ServiceSecoursStep6;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursStep6Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('hidden', HiddenType::class, ['mapped' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServiceSecoursStep6::class,
        ]);
    }
}
