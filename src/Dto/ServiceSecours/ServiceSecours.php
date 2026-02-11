<?php

namespace App\Dto\ServiceSecours;

use Symfony\Component\Validator\Constraints\Valid;

class ServiceSecours
{
    public function __construct(
        #[Valid(groups: ['step1'])]
        public ServiceSecoursStep1 $step1 = new ServiceSecoursStep1(),

        #[Valid(groups: ['step2'])]
        public ServiceSecoursStep2 $step2 = new ServiceSecoursStep2(),

        #[Valid(groups: ['step3'])]
        public ServiceSecoursStep3 $step3 = new ServiceSecoursStep3(),

        #[Valid(groups: ['step4'])]
        public ServiceSecoursStep4 $step4 = new ServiceSecoursStep4(),

        #[Valid(groups: ['step5'])]
        public ServiceSecoursStep5 $step5 = new ServiceSecoursStep5(),

        #[Valid(groups: ['step6'])]
        public ServiceSecoursStep6 $step6 = new ServiceSecoursStep6(),

        public string $currentStep = 'step1',
    ) {
    }
}
