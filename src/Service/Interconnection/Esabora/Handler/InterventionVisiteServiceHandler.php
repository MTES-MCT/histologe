<?php

namespace App\Service\Interconnection\Esabora\Handler;

use App\Entity\Affectation;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;

class InterventionVisiteServiceHandler implements InterventionSISHHandlerInterface
{
    private int $countSuccess = 0;
    private int $countFailed = 0;

    public function __construct(
        private readonly EsaboraSISHService $esaboraSISHService,
        private readonly EsaboraManager $esaboraManager,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function handle(Affectation $affectation): void
    {
        $hasDateError = false;
        $dossierVisiteSISHCollectionResponse = $this->esaboraSISHService->getVisiteDossier($affectation);
        if (AbstractEsaboraService::hasSuccess($dossierVisiteSISHCollectionResponse)) {
            foreach ($dossierVisiteSISHCollectionResponse->getCollection() as $dossierVisite) {
                if (!$dossierVisite->getVisiteDate()) {
                    $hasDateError = true;
                    continue;
                }
                $this->esaboraManager->createOrUpdateVisite($affectation, $dossierVisite);
            }
            ++$this->countSuccess;
        } else {
            ++$this->countFailed;
        }

        if ($hasDateError) {
            throw new \Exception('Date de visite manquante');
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
        return AbstractEsaboraService::ACTION_SYNC_DOSSIER_VISITE;
    }

    public static function getPriority(): int
    {
        return 1;
    }
}
