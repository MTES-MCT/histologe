<?php

namespace App\Service\Esabora\Handler;

use App\Entity\Affectation;
use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use App\Service\Esabora\AbstractEsaboraService;
use App\Service\Esabora\EsaboraManager;
use App\Service\Esabora\EsaboraSISHService;
use Symfony\Component\Serializer\SerializerInterface;

class InterventionArreteServiceHandler implements InterventionSISHHandlerInterface
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
        $dossierArreteCollection = $this->esaboraSISHService->getArreteDossier($affectation);
        foreach ($dossierArreteCollection as $dossierArrete) {
            $this->esaboraManager->createArrete($affectation, $dossierArrete);
        }

        $this->jobEventManager->createJobEvent(
            service: AbstractEsaboraService::TYPE_SERVICE,
            action: AbstractEsaboraService::ACTION_SYNC_DOSSIER_ARRETE,
            message: $this->serializer->serialize(
                $this->esaboraSISHService->prepareInterventionPayload(
                    $affectation,
                    AbstractEsaboraService::SISH_ARRETES_DOSSIER_SAS
                ),
                'json'
            ),
            response: $this->serializer->serialize($dossierArreteCollection, 'json'),
            status: 200 === $dossierArreteCollection->getStatusCode() ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED,
            codeStatus: $dossierArreteCollection->getStatusCode(),
            signalementId: $affectation->getId(),
            partnerId: $affectation->getPartner()->getId(),
            partnerType: $affectation->getPartner()->getType(),
        );
    }

    public function getServiceName(): string
    {
        return AbstractEsaboraService::ACTION_SYNC_DOSSIER_ARRETE;
    }

    public static function getPriority(): int
    {
        return 2;
    }
}
