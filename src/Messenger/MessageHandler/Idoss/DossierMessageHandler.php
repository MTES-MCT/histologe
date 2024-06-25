<?php

namespace App\Messenger\MessageHandler\Idoss;

use App\Entity\JobEvent;
use App\Messenger\Message\Idoss\DossierMessage;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Service\Idoss\IdossService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DossierMessageHandler
{
    public function __construct(
        private readonly IdossService $idossService,
        private readonly SignalementRepository $signalementRepository,
        private readonly PartnerRepository $partnerRepository,
    ) {
    }

    public function __invoke(DossierMessage $dossierMessage): void
    {
        $jobEvent = $this->idossService->pushDossier($dossierMessage);
        if (JobEvent::STATUS_SUCCESS === $jobEvent->getStatus()) {
            $signalement = $this->signalementRepository->find($dossierMessage->getSignalementId());
            $partner = $this->partnerRepository->find($dossierMessage->getPartnerId());
            $this->idossService->uploadFiles($partner, $signalement);
        }
    }
}
