<?php

namespace App\Messenger\MessageHandler;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Factory\FileFactory;
use App\Messenger\Message\SignalementDraftFileMessage;
use App\Repository\SignalementDraftRepository;
use App\Repository\SignalementRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SignalementDraftFileMessageHandler
{
    public function __construct(
        private SignalementRepository $signalementRepository,
        private SignalementDraftRepository $signalementDraftRepository,
        private SignalementDraftRequestSerializer $signalementDraftRequestSerializer,
        private FileFactory $fileFactory,
        private UploadHandlerService $uploadHandlerService,
        private LoggerInterface $logger,
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

        if ($files = $signalementDraftRequest->getFiles()) {
            foreach ($files as $key => $fileList) {
                foreach ($fileList as $fileItem) {
                    $fileItem['slug'] = $key;
                    $file = $this->fileFactory->createFromFileArray(file: $fileItem);
                    $this->uploadHandlerService->moveFromBucketTempFolder($file->getFilename());
                    $file->setSize($this->uploadHandlerService->getFileSize($file->getFilename()));
                    $file->setIsVariantsGenerated($this->uploadHandlerService->hasVariants($file->getFilename()));
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
