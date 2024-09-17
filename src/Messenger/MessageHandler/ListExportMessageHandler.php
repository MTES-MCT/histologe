<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\ListExportMessage;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\Export\SignalementExportLoader;
use App\Service\TimezoneProvider;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListExportMessageHandler
{
    public function __construct(
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly LoggerInterface $logger,
        private readonly SignalementExportLoader $signalementExportLoader,
        private readonly UserRepository $userRepository,
        private ParameterBagInterface $parameterBag,
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
                $datetimeStr = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone($timezone))->format('Ymd-Hi');
                $filename = 'export-histologe-'.$listExportMessage->getUserId().'-'.$datetimeStr.'.'.$format;
                $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;
                $writer->save($tmpFilepath);

                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_LIST_EXPORT,
                        to: $user->getEmail(),
                        attachment: $tmpFilepath
                    )
                );
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
