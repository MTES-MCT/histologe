<?php

namespace App\Factory\Interconnection\Idoss;

use App\Entity\Affectation;
use App\Messenger\Message\Idoss\DossierMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DossierMessageFactory
{
    public function __construct(
        #[Autowire(env: 'FEATURE_IDOSS_ENABLE')]
        private bool $featureEnable,
    ) {
    }

    public function supports(Affectation $affectation): bool
    {
        if (!$this->featureEnable) {
            return false;
        }
        if (Affectation::STATUS_ACCEPTED !== $affectation->getStatut()) {
            return false;
        }
        if (!$affectation->getPartner()->canSyncWithIdoss()) {
            return false;
        }

        return true;
    }

    public function createInstance(Affectation $affectation): DossierMessage
    {
        return new DossierMessage($affectation);
    }
}
