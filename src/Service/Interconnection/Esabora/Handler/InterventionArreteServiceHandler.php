<?php

namespace App\Service\Interconnection\Esabora\Handler;

use App\Entity\Affectation;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Normalizer\ArreteSISHCollectionResponseNormalizer;

class InterventionArreteServiceHandler implements InterventionSISHHandlerInterface
{
    private int $countSuccess = 0;
    private int $countFailed = 0;

    public function __construct(
        private readonly EsaboraSISHService $esaboraSISHService,
        private readonly ArreteSISHCollectionResponseNormalizer $arreteSISHCollectionResponseNormalizer,
        private readonly EsaboraManager $esaboraManager,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function handle(Affectation $affectation, string $uuidSignalement): void
    {
        $dossierArreteSISHCollectionResponse = $this->esaboraSISHService->getArreteDossier($affectation, $uuidSignalement);
        if (AbstractEsaboraService::hasSuccess($dossierArreteSISHCollectionResponse)) {
            $dossierArreteSISHCollectionResponseNormalized = $this->arreteSISHCollectionResponseNormalizer->normalize($dossierArreteSISHCollectionResponse);
            foreach ($dossierArreteSISHCollectionResponseNormalized->getCollection() as $dossierArrete) {
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
