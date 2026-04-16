<?php

namespace App\Service\Interconnection\Esabora\Handler;

use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Model\DossierMessageSISHPersonne;

class DossierPersonneServiceHandler extends AbstractDossierSISHHandler
{
    protected ?string $action = AbstractEsaboraService::ACTION_PUSH_DOSSIER_PERSONNE;

    public function __construct(private readonly EsaboraSISHService $esaboraSISHService)
    {
        parent::__construct();
    }

    public function handle(DossierMessageSISH $dossierMessageSISH): bool
    {
        $dossierMessageSISH
            ->setAction($this->action)
            ->setAttachmentsSize(null)
            ->setAttachmentsCount(null);
        /** @var DossierMessageSISHPersonne $dossierPersonne */
        foreach ($dossierMessageSISH->getPersonnes() as $dossierPersonne) {
            $this->response = $this->esaboraSISHService->pushPersonne($dossierMessageSISH, $dossierPersonne);
            parent::handle($dossierMessageSISH);
        }

        return true; // last SISH web service called; no need to handle further
    }

    public static function getPriority(): int
    {
        return 1;
    }
}
