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
    private int $countSuccess = 0;
    private int $countFailed = 0;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly EsaboraSISHService $esaboraSISHService,
        private readonly EsaboraManager $esaboraManager,
        private readonly JobEventManager $jobEventManager,
    ) {
    }

    public function handle(Affectation $affectation): void
    {
        $dossierArreteSISHCollectionResponse = $this->esaboraSISHService->getArreteDossier($affectation);
        if ($hasSuccess = AbstractEsaboraService::hasSuccess($dossierArreteSISHCollectionResponse)) {
            foreach ($dossierArreteSISHCollectionResponse->getCollection() as $dossierArrete) {
                $this->esaboraManager->createOrUpdateArrete($affectation, $dossierArrete);
            }
            ++$this->countSuccess;
        } else {
            ++$this->countFailed;
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
            response: $this->serializer->serialize($dossierArreteSISHCollectionResponse, 'json'),
            status: $hasSuccess
                ? JobEvent::STATUS_SUCCESS
                : JobEvent::STATUS_FAILED,
            codeStatus: $dossierArreteSISHCollectionResponse->getStatusCode(),
            signalementId: $affectation->getSignalement()->getId(),
            partnerId: $affectation->getPartner()->getId(),
            partnerType: $affectation->getPartner()->getType(),
        );
    }

    public function getCountSuccess(): int
    {
        return $this->countSuccess;
    }

    public function getCountFailed(): int
    {
        return $this->countFailed;
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
