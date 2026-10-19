<?php

namespace App\Service\Interconnection\Esabora\Handler;

use App\Entity\Affectation;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Normalizer\ArreteSISHCollectionResponseNormalizer;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 2)]
class InterventionArreteServiceHandler implements InterventionSISHHandlerInterface
{
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
            $dossierArreteSISHCollectionResponseNormalized = $this->arreteSISHCollectionResponseNormalizer->normalize(
                $dossierArreteSISHCollectionResponse
            );
            foreach ($dossierArreteSISHCollectionResponseNormalized->getCollection() as $dossierArrete) {
                $this->esaboraManager->createOrUpdateArrete($affectation, $dossierArrete);
            }
        }
    }

    public function getServiceName(): string
    {
        return AbstractEsaboraService::ACTION_SYNC_DOSSIER_ARRETE;
    }
}
