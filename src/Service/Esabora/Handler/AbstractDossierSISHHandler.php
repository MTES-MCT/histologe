<?php

namespace App\Service\Esabora\Handler;

use App\Entity\JobEvent;
use App\Entity\Partner;
use App\Manager\JobEventManager;
use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Repository\PartnerRepository;
use App\Service\Esabora\AbstractEsaboraService;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractDossierSISHHandler implements DossierSISHHandlerInterface
{
    protected ?Partner $partner = null;
    protected ?string $action = null;
    protected mixed $response = null;
    protected ?int $sasAdresseId = null;
    protected ?int $sasDossierId = null;
    protected string $status = JobEvent::STATUS_FAILED;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly JobEventManager $jobEventManager,
        private readonly PartnerRepository $partnerRepository
    ) {
    }

    public function handle(DossierMessageSISH $dossierMessageSISH): void
    {
        $this->sasAdresseId = $dossierMessageSISH->getSasAdresse();
        $this->sasDossierId = $dossierMessageSISH->getSasDossierId();
        $this->status = 200 === $this->response->getStatusCode() ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED;

        $this->partner = $this->partnerRepository->find($dossierMessageSISH->getPartnerId());
        $this->jobEventManager->createJobEvent(
            service: AbstractEsaboraService::TYPE_SERVICE,
            action: $this->action,
            message: $this->serializer->serialize($dossierMessageSISH, 'json'),
            response: $this->serializer->serialize($this->response, 'json'),
            status: $this->status,
            codeStatus: $this->response->getStatusCode(),
            signalementId: $dossierMessageSISH->getSignalementId(),
            partnerId: $this->partner?->getId(),
            partnerType: $this->partner?->getType(),
        );
    }

    public function canFlagAsSynchronized(): bool
    {
        return AbstractEsaboraService::ACTION_PUSH_DOSSIER_PERSONNE === $this->action
            && JobEvent::STATUS_SUCCESS === $this->status;
    }
}
