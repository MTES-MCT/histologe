<?php

namespace App\Messenger\MessageHandler;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\User;
use App\Manager\SignalementManager;
use App\Manager\UserManager;
use App\Messenger\Message\SignalementDraftProcessMessage;
use App\Repository\SignalementDraftRepository;
use App\Repository\SignalementRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\Files\SignalementFileAttacher;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 10)]
class SignalementDraftFileMessageHandler
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly SignalementManager $signalementManager,
        private readonly SignalementDraftRepository $signalementDraftRepository,
        private readonly SignalementDraftRequestSerializer $signalementDraftRequestSerializer,
        private readonly LoggerInterface $logger,
        private readonly UserManager $userManager,
        private readonly SignalementFileAttacher $signalementFileAttacher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(SignalementDraftProcessMessage $signalementDraftProcessMessage): void
    {
        $this->logger->info('Start handling SignalementDraftFileMessageHandler', [
            'signalementDraftId' => $signalementDraftProcessMessage->getSignalementDraftId(),
            'signalementId' => $signalementDraftProcessMessage->getSignalementId(),
            'step' => 'send-files',
        ]);

        $signalementDraft = $this->signalementDraftRepository->find(
            $signalementDraftProcessMessage->getSignalementDraftId()
        );

        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $this->signalementDraftRequestSerializer->denormalize(
            $signalementDraft->getPayload(),
            SignalementDraftRequest::class
        );

        $signalement = $this->signalementRepository->find($signalementDraftProcessMessage->getSignalementId());
        /** @var User|null $uploadUser */
        $uploadUser = $this->userManager->findOneBy(['email' => $signalement->getMailDeclarant()]);
        if ($files = $signalementDraftRequest->getFiles()) {
            foreach ($files as $key => $fileList) {
                foreach ($fileList as $fileItem) {
                    $fileItem['slug'] = $key;
                    $this->signalementFileAttacher->createAndAttach(
                        signalement: $signalement,
                        fileData: $fileItem,
                        uploadedBy: $uploadUser
                    );
                }
            }
            $this->signalementManager->save($signalement);
            $this->entityManager->flush();
        }

        $this->logger->info('SignalementDraftFileMessageHandler handled successfully', [
            'signalementId' => $signalementDraftProcessMessage->getSignalementId(),
            'nbFiles' => \count($signalementDraftRequest->getFiles()),
            'step' => 'send-files',
        ]);
    }
}
