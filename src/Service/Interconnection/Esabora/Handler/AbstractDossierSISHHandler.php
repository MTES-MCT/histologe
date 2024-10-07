<?php

namespace App\Service\Interconnection\Esabora\Handler;

use App\Entity\JobEvent;
use App\Entity\Partner;
use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractDossierSISHHandler implements DossierSISHHandlerInterface
{
    protected ?Partner $partner = null;
    protected ?string $action = null;
    protected mixed $response = null;
    protected ?int $sasAdresseId = null;
    protected ?int $sasDossierId = null;
    protected string $status = JobEvent::STATUS_FAILED;

    public function __construct()
    {
    }

    public function handle(DossierMessageSISH $dossierMessageSISH): void
    {
        $this->sasAdresseId = $dossierMessageSISH->getSasAdresse();
        $this->sasDossierId = $dossierMessageSISH->getSasDossierId();
        $this->status = Response::HTTP_OK === $this->response->getStatusCode() ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED;
    }

    public function canFlagAsSynchronized(): bool
    {
        return AbstractEsaboraService::ACTION_PUSH_DOSSIER_PERSONNE === $this->action
            && JobEvent::STATUS_SUCCESS === $this->status;
    }
}
