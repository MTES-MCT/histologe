<?php

namespace App\Service\Interconnection\Esabora\Handler;

use App\Entity\Affectation;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 1)]
class InterventionVisiteServiceHandler implements InterventionSISHHandlerInterface
{
    public function __construct(
        private readonly EsaboraSISHService $esaboraSISHService,
        private readonly EsaboraManager $esaboraManager,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function handle(Affectation $affectation, string $uuidSignalement): void
    {
        $hasDateError = false;
        $dossierVisiteSISHCollectionResponse = $this->esaboraSISHService->getVisiteDossier($affectation, $uuidSignalement);
        if (AbstractEsaboraService::hasSuccess($dossierVisiteSISHCollectionResponse)) {
            foreach ($dossierVisiteSISHCollectionResponse->getCollection() as $dossierVisite) {
                if (!$dossierVisite->getVisiteDate()) {
                    $hasDateError = true;
                    continue;
                }
                $this->esaboraManager->createOrUpdateVisite($affectation, $dossierVisite);
            }
        }

        if ($hasDateError) {
            throw new \Exception('Date de visite manquante');
        }
    }

    public function getServiceName(): string
    {
        return AbstractEsaboraService::ACTION_SYNC_DOSSIER_VISITE;
    }
}
