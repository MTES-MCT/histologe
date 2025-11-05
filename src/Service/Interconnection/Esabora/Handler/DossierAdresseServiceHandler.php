<?php

namespace App\Service\Interconnection\Esabora\Handler;

use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraSISHService;

class DossierAdresseServiceHandler extends AbstractDossierSISHHandler
{
    protected ?string $action = AbstractEsaboraService::ACTION_PUSH_DOSSIER_ADRESSE;

    public function __construct(
        private readonly EsaboraSISHService $esaboraSISHService,
    ) {
        parent::__construct();
    }

    public function handle(DossierMessageSISH $dossierMessageSISH): void
    {
        $dossierMessageSISH
            ->setAction($this->action)
            ->setAttachmentsSize(null)
            ->setAttachmentsCount(null);
        $this->response = $this->esaboraSISHService->pushAdresse($dossierMessageSISH);
        $dossierMessageSISH->setSasAdresse($this->response->getSasId());
        parent::handle($dossierMessageSISH);
    }

    public static function getPriority(): int
    {
        return 3;
    }
}
