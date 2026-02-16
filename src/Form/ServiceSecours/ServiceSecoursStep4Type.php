<?php

namespace App\Form\ServiceSecours;

use App\Dto\ServiceSecours\FormServiceSecoursStep4;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursStep4Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('isBailleurAverti', ChoiceType::class, [
            'label' => 'Bailleur averti <span class="text-required">*</span>',
            'label_html' => true,
            'required' => false,
            'placeholder' => false,
            'expanded' => true,
            'choices' => [
                'Oui' => true,
                'Non' => false,
                'Indeterminé' => null,
            ],
        ]);
        $builder->add('denominationBailleur', null, ['label' => 'Dénomination du bailleur']);
        $builder->add('mailBailleur', null, ['label' => 'Adresse e-mail', 'help' => 'Format attendu : nom@domaine.fr']);
        $builder->add('telBailleur', null, ['label' => 'Téléphone']);
        // TODO : ajouter les coordonnées du syndic
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormServiceSecoursStep4::class,
        ]);
    }
}
