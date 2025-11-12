<?php

namespace App\Service\Interconnection\Esabora\Handler;

use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\AttachmentsUtils;
use App\Service\Interconnection\Esabora\EsaboraSISHService;

class DossierServiceHandler extends AbstractDossierSISHHandler
{
    protected ?string $action = AbstractEsaboraService::ACTION_PUSH_DOSSIER;

    public function __construct(private readonly EsaboraSISHService $esaboraSISHService)
    {
        parent::__construct();
    }

    public function handle(DossierMessageSISH $dossierMessageSISH): void
    {
        $piecesJointes = $dossierMessageSISH->getPiecesJointesDocuments();
        if (!empty($piecesJointes)) {
            $attachmentsCount = count($piecesJointes);
            $attachmentsSize = AttachmentsUtils::computeTotalSize($piecesJointes);
            $dossierMessageSISH
                ->setAttachmentsCount($attachmentsCount)
                ->setAttachmentsSize($attachmentsSize);
        }

        $dossierMessageSISH->setAction($this->action);
        $this->response = $this->esaboraSISHService->pushDossier($dossierMessageSISH);
        $dossierMessageSISH->setSasDossierId($this->response->getSasId());
        parent::handle($dossierMessageSISH);
    }

    public static function getPriority(): int
    {
        return 2;
    }
}
