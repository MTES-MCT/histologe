<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\DossierMessageSISH;
use App\Service\Esabora\DossierMessageSISHPersonne;
use App\Service\Esabora\EsaboraSISHService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DossierMessageSISHHandler
{
    public function __construct(private readonly EsaboraSISHService $esaboraSISHService)
    {
    }

    public function __invoke(DossierMessageSISH $sishDossierMessage): void
    {
        $responsePushAdresse = $this->esaboraSISHService->pushAdresse($sishDossierMessage);
        $sishDossierMessage->setSasAdresse($responsePushAdresse->getSasId());

        $responsePushDossier = $this->esaboraSISHService->pushDossier($sishDossierMessage);
        $sishDossierMessage->setSasDossierId($responsePushDossier->getSasId());

        /** @var DossierMessageSISHPersonne $dossierPersonne */
        foreach ($sishDossierMessage->getPersonnes() as $dossierPersonne) {
            $this->esaboraSISHService->pushPersonne($sishDossierMessage, $dossierPersonne);
        }
    }
}
