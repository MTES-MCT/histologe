<?php

namespace App\Factory;

use App\Dto\Api\Request\VisiteRequest as VisiteRequestApi;
use App\Dto\Request\Signalement\VisiteRequest;
use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\User;
use App\Service\Signalement\DescriptionFilesBuilder;
use Symfony\Bundle\SecurityBundle\Security;

readonly class SignalementVisiteRequestFactory
{
    public function __construct(private DescriptionFilesBuilder $descriptionFilesBuilder, private Security $security)
    {
    }

    /**
     * @throws \Exception
     */
    public function createFrom(VisiteRequestApi $visiteRequest, Signalement $signalement): VisiteRequest
    {
        $affectation = $this->getAffectation($signalement);

        return new VisiteRequest(
            date: $visiteRequest->date,
            time: $visiteRequest->time,
            timezone: $signalement->getTimezone(),
            idPartner: $affectation->getPartner()->getId(),
            details: $this->descriptionFilesBuilder->build($signalement, $visiteRequest),
            concludeProcedure: $visiteRequest->concludeProcedure,
            isVisiteDone: true,
            isOccupantPresent: $visiteRequest->occupantPresent,
            isProprietairePresent: $visiteRequest->proprietairePresent,
            isUsagerNotified: $visiteRequest->notifyUsager
        );
    }

    private function getAffectation(Signalement $signalement): Affectation
    {
        /** @var User $user */
        $user = $this->security->getUser();

        return $signalement
            ->getAffectations()
            ->filter(function (Affectation $affectation) use ($user) {
                return $user->hasPartner($affectation->getPartner());
            })
            ->current();
    }
}
