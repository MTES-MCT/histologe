<?php

namespace App\Messenger\MessageHandler\Idoss;

use App\Entity\JobEvent;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Messenger\Message\Idoss\DossierMessage;
use App\Service\Idoss\IdossService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DossierMessageHandler
{
    public function __construct(
        private readonly IdossService $idossService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(DossierMessage $dossierMessage): void
    {
        $jobEvent = $this->idossService->pushDossier($dossierMessage);
        if (JobEvent::STATUS_SUCCESS === $jobEvent->getStatus()) {
            $signalement = $this->entityManager->getRepository(Signalement::class)->find($dossierMessage->getSignalementId());
            $partner = $this->entityManager->getRepository(Partner::class)->find($dossierMessage->getPartnerId());
            $this->idossService->uploadFiles($partner, $signalement);
        }
    }
}
