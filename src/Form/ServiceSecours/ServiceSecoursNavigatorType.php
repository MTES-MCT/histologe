<?php

namespace App\Form\ServiceSecours;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Flow\FormFlowCursor;
use Symfony\Component\Form\Flow\Type\FinishFlowType;
use Symfony\Component\Form\Flow\Type\NextFlowType;
use Symfony\Component\Form\Flow\Type\PreviousFlowType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursNavigatorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('previous', PreviousFlowType::class, [
                'label' => 'Précédent',
                'include_if' => fn (FormFlowCursor $cursor) => !$cursor->isFirstStep(),
                'attr' => ['class' => 'fr-btn--secondary'],
            ])
            ->add('next', NextFlowType::class, [
                'label' => 'Suivant',
                'include_if' => fn (FormFlowCursor $cursor) => !$cursor->isLastStep(),
            ])
            ->add('finish', FinishFlowType::class, [
                'label' => 'Valider le signalement',
                'include_if' => fn (FormFlowCursor $cursor) => $cursor->isLastStep(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'mapped' => false,
        ]);
    }
}
