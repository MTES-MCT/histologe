<?php

namespace App\Messenger\MessageHandler;

use App\Entity\Enum\DocumentType;
use App\Manager\FileManager;
use App\Messenger\Message\ListExportMessage;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\Export\SignalementExportLoader;
use App\Service\TimezoneProvider;
use App\Service\UploadHandlerService;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class ListExportMessageHandler
{
    public function __construct(
        private NotificationMailerRegistry $notificationMailerRegistry,
        private LoggerInterface $logger,
        private SignalementExportLoader $signalementExportLoader,
        private UserRepository $userRepository,
        private ParameterBagInterface $parameterBag,
        private UploadHandlerService $uploadHandlerService,
        private FileManager $fileManager
    ) {
    }

    public function __invoke(ListExportMessage $listExportMessage): void
    {
        try {
            $user = $this->userRepository->find($listExportMessage->getUserId());
            $filters = $listExportMessage->getFilters();
            $selectedColumns = $listExportMessage->getSelectedColumns();
            $format = $listExportMessage->getFormat();

            $spreadsheet = $this->signalementExportLoader->load($user, $filters, $selectedColumns);
            if ('csv' === $format) {
                $writer = new Csv($spreadsheet);
            } elseif ('xlsx' === $format) {
                $writer = new Xlsx($spreadsheet);
            }

            if (isset($writer)) {
                $timezone = $user->getTerritory()?->getTimezone() ?? TimezoneProvider::TIMEZONE_EUROPE_PARIS;
                $datetimeStr = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone($timezone))->format('Ymd-His');
                $uuid = Uuid::v4();
                $filename = 'export-histologe-'.$listExportMessage->getUserId().'-'.$datetimeStr.'-'.$uuid.'.'.$format;
                $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;
                $writer->save($tmpFilepath);

                $filename = $this->uploadHandlerService->uploadFromFilename($filename);
                if ($filename) {
                    $file = $this->fileManager->createOrUpdate(
                        filename: $filename,
                        title: $filename,
                        type: 'document',
                        user: $user,
                        flush: true,
                        documentType: DocumentType::EXPORT
                    );

                    $this->notificationMailerRegistry->send(
                        new NotificationMail(
                            type: NotificationMailerType::TYPE_LIST_EXPORT,
                            to: $user->getEmail(),
                            params: [
                                'filename' => $filename,
                                'file_uuid' => $file->getUuid(),
                            ]
                        )
                    );
                } else {
                    $this->logger->error('There was an issue generating your export');
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'The export of list failed (%s) for the following reason : %s',
                    $listExportMessage->getUserId(),
                    $exception->getMessage()
                )
            );
        }
    }
}
