<?php

namespace App\Factory;

use App\Dto\Api\Request\VisiteRequest as VisiteRequestApi;
use App\Dto\Request\Signalement\VisiteRequest;
use App\Entity\Affectation;
use App\Service\Signalement\DescriptionFilesBuilder;

readonly class SignalementVisiteRequestFactory
{
    public function __construct(private DescriptionFilesBuilder $descriptionFilesBuilder)
    {
    }

    /**
     * @throws \Exception
     */
    public function createFrom(VisiteRequestApi $visiteRequest, Affectation $affectation): VisiteRequest
    {
        $signalement = $affectation->getSignalement();

        return new VisiteRequest(
            date: $visiteRequest->date,
            time: $visiteRequest->time,
            timezone: $signalement->getTimezone(),
            idPartner: $affectation->getPartner()->getId(),
            commentBeforeVisite: $visiteRequest->commentBeforeVisite,
            details: $this->descriptionFilesBuilder->build($signalement, $visiteRequest),
            concludeProcedure: $visiteRequest->concludeProcedure,
            isVisiteDone: $visiteRequest->visiteEffectuee,
            isOccupantPresent: $visiteRequest->occupantPresent,
            isProprietairePresent: $visiteRequest->proprietairePresent,
            isUsagerNotified: $visiteRequest->notifyUsager
        );
    }
}
