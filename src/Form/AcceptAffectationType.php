<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class AcceptAffectationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('agents', ChoiceType::class, [
            'choices' => $options['agents'],
            'multiple' => true,
            'expanded' => true,
            'choice_value' => fn ($value) => (string) $value,
            'label_html' => true,
            'label' => 'Sélectionnez le(s) agent(s) en charge du dossier',
            // TODO : le message d'erreur se place mal
            'constraints' => [
                new Count([
                    'min' => 1,
                    'minMessage' => 'Veuillez sélectionner au moins un agent.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('agents');
        $resolver->setDefaults([
            'agents' => [],
        ]);
    }
}
