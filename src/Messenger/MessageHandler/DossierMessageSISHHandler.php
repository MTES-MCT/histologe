<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\DossierMessageSISH;
use App\Service\Esabora\EsaboraSISHService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DossierMessageSISHHandler
{
    public function __construct(private readonly EsaboraSISHService $esaboraSISHService)
    {
    }

    public function __invoke(DossierMessageSISH $sishDossierMessage)
    {
        $response = $this->esaboraSISHService->pushAdresse($sishDossierMessage);

        $response = $this->esaboraSISHService->pushDossier($sishDossierMessage);

        $response = $this->esaboraSISHService->pushPersonne($sishDossierMessage);
    }
}
