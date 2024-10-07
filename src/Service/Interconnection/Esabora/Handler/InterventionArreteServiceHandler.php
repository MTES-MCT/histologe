<?php

namespace App\Service\Interconnection\Esabora\Handler;

use App\Entity\Affectation;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;

class InterventionArreteServiceHandler implements InterventionSISHHandlerInterface
{
    private int $countSuccess = 0;
    private int $countFailed = 0;

    public function __construct(
        private readonly EsaboraSISHService $esaboraSISHService,
        private readonly EsaboraManager $esaboraManager,
    ) {
    }

    public function handle(Affectation $affectation): void
    {
        $dossierArreteSISHCollectionResponse = $this->esaboraSISHService->getArreteDossier($affectation);
        if (AbstractEsaboraService::hasSuccess($dossierArreteSISHCollectionResponse)) {
            foreach ($dossierArreteSISHCollectionResponse->getCollection() as $dossierArrete) {
                $this->esaboraManager->createOrUpdateArrete($affectation, $dossierArrete);
            }
            ++$this->countSuccess;
        } else {
            ++$this->countFailed;
        }
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
