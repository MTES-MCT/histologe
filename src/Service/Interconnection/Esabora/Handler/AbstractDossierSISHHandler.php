<?php

namespace App\Service\Interconnection\Esabora\Handler;

use App\Entity\JobEvent;
use App\Entity\Partner;
use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractDossierSISHHandler implements DossierSISHHandlerInterface
{
    public const string ERROR_SQL_DUPLICATE_KEY = 'WS_ERR_SQL';

    protected ?Partner $partner = null;
    protected ?string $action = null;
    protected mixed $response = null;
    protected ?int $sasAdresseId = null;
    protected ?int $sasDossierId = null;
    protected string $status = JobEvent::STATUS_FAILED;
    protected ?string $errorCode = null;

    public function __construct()
    {
    }

    public function handle(DossierMessageSISH $dossierMessageSISH): bool
    {
        $this->sasAdresseId = $dossierMessageSISH->getSasAdresse();
        $this->sasDossierId = $dossierMessageSISH->getSasDossierId();
        $this->status = Response::HTTP_OK === $this->response->getStatusCode() ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED;
        if (JobEvent::STATUS_FAILED === $this->status) {
            $this->errorCode = $this->response->getErrorCode() ?? null;

            return false;
        }

        return true;
    }

    public function canFlagAsSynchronized(): bool
    {
        return AbstractEsaboraService::ACTION_PUSH_DOSSIER_PERSONNE === $this->action
            && JobEvent::STATUS_SUCCESS === $this->status
            || $this->dossierAlreadyExists();
    }

    public function dossierAlreadyExists(): bool
    {
        return self::ERROR_SQL_DUPLICATE_KEY === $this->errorCode;
    }
}
