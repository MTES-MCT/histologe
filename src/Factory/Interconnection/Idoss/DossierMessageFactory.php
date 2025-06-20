<?php

namespace App\Factory\Interconnection\Idoss;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Messenger\Message\Idoss\DossierMessage;
use App\Service\Interconnection\Idoss\IdossService;

class DossierMessageFactory
{
    public function supports(Affectation $affectation): bool
    {
        if (AffectationStatus::ACCEPTED !== $affectation->getStatut()) {
            return false;
        }
        if (!$affectation->getPartner()->canSyncWithIdoss()) {
            return false;
        }
        if ($affectation->getSignalement()->getSynchroData(IdossService::TYPE_SERVICE)) {
            return false;
        }

        return true;
    }

    public function createInstance(Affectation $affectation): DossierMessage
    {
        return new DossierMessage($affectation);
    }
}
