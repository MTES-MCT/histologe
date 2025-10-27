<?php

namespace App\Messenger\MessageHandler;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\User;
use App\Factory\FileFactory;
use App\Manager\UserManager;
use App\Messenger\Message\SignalementDraftFileMessage;
use App\Repository\SignalementDraftRepository;
use App\Repository\SignalementRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\Security\FileScanner;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SignalementDraftFileMessageHandler
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly SignalementDraftRepository $signalementDraftRepository,
        private readonly SignalementDraftRequestSerializer $signalementDraftRequestSerializer,
        private readonly FileFactory $fileFactory,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly LoggerInterface $logger,
        private readonly UserManager $userManager,
        private readonly FileScanner $fileScanner,
        #[Autowire(env: 'CLAMAV_SCAN_ENABLE')]
        private readonly bool $clamavScanEnable,
    ) {
    }

    public function __invoke(SignalementDraftFileMessage $signalementDraftFileMessage): void
    {
        $this->logger->info('Start handling SignalementDraftFileMessage', [
            'signalementDraftId' => $signalementDraftFileMessage->getSignalementDraftId(),
            'signalementId' => $signalementDraftFileMessage->getSignalementId(),
        ]);

        $signalementDraft = $this->signalementDraftRepository->find(
            $signalementDraftFileMessage->getSignalementDraftId()
        );

        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $this->signalementDraftRequestSerializer->denormalize(
            $signalementDraft->getPayload(),
            SignalementDraftRequest::class
        );

        $signalement = $this->signalementRepository->find($signalementDraftFileMessage->getSignalementId());
        /** @var User|null $uploadUser */
        $uploadUser = $this->userManager->findOneBy(['email' => $signalement->getMailDeclarant()]);
        if ($files = $signalementDraftRequest->getFiles()) {
            foreach ($files as $key => $fileList) {
                foreach ($fileList as $fileItem) {
                    $fileItem['slug'] = $key;
                    $file = $this->fileFactory->createFromFileArray(file: $fileItem, signalement: $signalement);
                    $this->uploadHandlerService->moveFromBucketTempFolder($file->getFilename());
                    $fileSize = $this->uploadHandlerService->getFileSize($file->getFilename());
                    $file->setSize(null !== $fileSize ? (string) $fileSize : null);
                    $file->setIsVariantsGenerated($this->uploadHandlerService->hasVariants($file->getFilename()));
                    $file->setUploadedBy($uploadUser);
                    if ($this->clamavScanEnable) {
                        $file->setScannedAt(new \DateTimeImmutable());
                        if (str_ends_with(strtolower($file->getFilename()), '.pdf')) {
                            $filepath = $this->uploadHandlerService->getTmpFilepath($file->getFilename());
                            if ($filepath && !$this->fileScanner->isClean($filepath)) {
                                $file->setIsSuspicious(true);
                            }
                        }
                    }
                    $signalement->addFile($file);
                }
            }
            $this->signalementRepository->save($signalement, true);
        }

        $this->logger->info('SignalementDraftFileMessage handled successfully', [
            'signalementId' => $signalementDraftFileMessage->getSignalementId(),
            'nbFiles' => \count($signalementDraftRequest->getFiles()),
        ]);
    }
}
