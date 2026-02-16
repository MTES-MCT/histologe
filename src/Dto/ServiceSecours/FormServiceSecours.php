<?php

namespace App\Dto\ServiceSecours;

use Symfony\Component\Validator\Constraints\Valid;

class FormServiceSecours
{
    public function __construct(
        #[Valid(groups: ['step1'])]
        public FormServiceSecoursStep1 $step1 = new FormServiceSecoursStep1(),

        #[Valid(groups: ['step2'])]
        public FormServiceSecoursStep2 $step2 = new FormServiceSecoursStep2(),

        #[Valid(groups: ['step3'])]
        public FormServiceSecoursStep3 $step3 = new FormServiceSecoursStep3(),

        #[Valid(groups: ['step4'])]
        public FormServiceSecoursStep4 $step4 = new FormServiceSecoursStep4(),

        #[Valid(groups: ['step5'])]
        public FormServiceSecoursStep5 $step5 = new FormServiceSecoursStep5(),

        #[Valid(groups: ['step6'])]
        public FormServiceSecoursStep6 $step6 = new FormServiceSecoursStep6(),

        public string $currentStep = 'step1',
    ) {
    }
}
