<?php

namespace App\Form;

use App\Dto\ReponseInjonctionBailleur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class ReponseInjonctionBailleurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reponse', ChoiceType::class, [
                'label' => 'Je m\'engage à résoudre les désordres',
                'choices' => [
                    'Oui' => ReponseInjonctionBailleur::REPONSE_OUI,
                    'Oui avec aide' => ReponseInjonctionBailleur::REPONSE_OUI_AVEC_AIDE,
                    'Non' => ReponseInjonctionBailleur::REPONSE_NON,
                ],
                'required' => false,
                'placeholder' => false,
                'expanded' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Commentaire',
                'help' => 'Dix (10) caractères minimum',
                'attr' => [
                    'rows' => 5,
                ],
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Confirmer ma réponse',
                'attr' => [
                    'class' => 'fr-btn--primary',
                ],
            ]);
    }
}
