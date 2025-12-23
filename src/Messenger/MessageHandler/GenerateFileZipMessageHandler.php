<?php

namespace App\Messenger\MessageHandler;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use App\Manager\FileManager;
use App\Messenger\Message\GenerateFileZipMessage;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\Files\ZipStreamBuilder;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\TimezoneProvider;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GenerateFileZipMessageHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SignalementRepository $signalementRepository,
        private readonly ZipStreamBuilder $zipBuilder,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly FileManager $fileManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws \DateInvalidTimeZoneException
     */
    public function __invoke(GenerateFileZipMessage $generateFileZipMessage): void
    {
        $user = $this->userRepository->find($generateFileZipMessage->getUserId());
        $signalement = $this->signalementRepository->find($generateFileZipMessage->getSignalementId());
        $fileIds = $generateFileZipMessage->getFileIds();
        $files = $this->getFiles($signalement, $fileIds);
        $exportedAt = $this->getExportAt($user);
        $zipName = sprintf('export-files-%s-%s.zip', $signalement->getUuid(), $exportedAt);

        try {
            $zipPath = $this->zipBuilder
                ->create($zipName)
                ->addMany($files)
                ->close();

            $filename = $this->uploadHandlerService->uploadFromFilename(basename($zipPath));
            if (!$filename) {
                return;
            }

            $file = $this->fileManager->createOrUpdate(
                filename: $zipName,
                title: $zipName,
                user: $user,
                flush: true,
                documentType: DocumentType::EXPORT
            );

            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_DOWNLOAD_EXPORT,
                    to: $user->getEmail(),
                    signalement: $signalement,
                    params: [
                        'filename' => $filename,
                        'file_uuid' => $file->getUuid(),
                        'message' => 'Vos photos sont prêtes à être téléchargées.',
                    ]
                )
            );
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * @throws \DateInvalidTimeZoneException
     */
    private function getExportAt(User $user): string
    {
        $timezone = $user->getFirstTerritory()?->getTimezone() ?? TimezoneProvider::TIMEZONE_EUROPE_PARIS;

        return (new \DateTimeImmutable())->setTimezone(new \DateTimeZone($timezone))->format('Ymd-His');
    }

    private function getFiles(Signalement $signalement, array $fileIds): array
    {
        $files = $signalement->getFiles()->filter(function (File $file) use ($fileIds) {
            return empty($fileIds)
                ? $file->isSituationImage()
                : in_array($file->getId(), $fileIds, true);
        });

        return $files->toArray();
    }
}
