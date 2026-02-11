<?php

namespace App\Form\ServiceSecours;

use App\Dto\ServiceSecours\ServiceSecours;
use Symfony\Component\Form\Flow\AbstractFlowType;
use Symfony\Component\Form\Flow\FormFlowBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursType extends AbstractFlowType
{
    public function buildFormFlow(FormFlowBuilderInterface $builder, array $options): void
    {
        $builder->addStep('step1', ServiceSecoursStep1Type::class);
        $builder->addStep('step2', ServiceSecoursStep2Type::class);
        $builder->addStep('step3', ServiceSecoursStep3Type::class);
        $builder->addStep('step4', ServiceSecoursStep4Type::class);
        $builder->addStep('step5', ServiceSecoursStep5Type::class);
        $builder->addStep('step6', ServiceSecoursStep6Type::class);

        $builder->add('navigator', ServiceSecoursNavigatorType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServiceSecours::class,
            'step_property_path' => 'currentStep',
        ]);
    }
}
