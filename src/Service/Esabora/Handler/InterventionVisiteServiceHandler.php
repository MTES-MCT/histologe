<?php

namespace App\Service\Esabora\Handler;

use App\Entity\Affectation;
use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use App\Service\Esabora\AbstractEsaboraService;
use App\Service\Esabora\EsaboraManager;
use App\Service\Esabora\EsaboraSISHService;
use Symfony\Component\Serializer\SerializerInterface;

class InterventionVisiteServiceHandler implements InterventionSISHHandlerInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly EsaboraSISHService $esaboraSISHService,
        private readonly EsaboraManager $esaboraManager,
        private readonly JobEventManager $jobEventManager,
    ) {
    }

    public function handle(Affectation $affectation): void
    {
        $dossierVisiteCollection = $this->esaboraSISHService->getVisiteDossier($affectation);
        foreach ($dossierVisiteCollection as $dossierVisite) {
            $this->esaboraManager->createVisite($affectation, $dossierVisite);
        }

        $this->jobEventManager->createJobEvent(
            service: AbstractEsaboraService::TYPE_SERVICE,
            action: AbstractEsaboraService::ACTION_SYNC_DOSSIER_VISITE,
            message: $this->serializer->serialize(
                $this->esaboraSISHService->prepareInterventionPayload(
                    $affectation,
                    AbstractEsaboraService::SISH_VISITES_DOSSIER_SAS
                ),
                'json'
            ),
            response: $this->serializer->serialize($dossierVisiteCollection, 'json'),
            status: 200 === $dossierVisiteCollection->getStatusCode() ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED,
            codeStatus: $dossierVisiteCollection->getStatusCode(),
            signalementId: $affectation->getId(),
            partnerId: $affectation->getPartner()->getId(),
            partnerType: $affectation->getPartner()->getType(),
        );
    }

    public function getServiceName(): string
    {
        return AbstractEsaboraService::ACTION_SYNC_DOSSIER_VISITE;
    }

    public static function getPriority(): int
    {
        return 1;
    }
}
