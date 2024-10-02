<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\UserExportMessage;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\TimezoneProvider;
use App\Service\UserExportLoader;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UserExportMessageHandler
{
    public function __construct(
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly LoggerInterface $logger,
        private readonly UserExportLoader $userExportLoader,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function __invoke(UserExportMessage $listExportMessage): void
    {
        try {
            $user = $listExportMessage->getSearchUser()->getUser();
            $format = $listExportMessage->getFormat();
            $spreadsheet = $this->userExportLoader->load($listExportMessage->getSearchUser());
            if ('csv' === $format) {
                $writer = new Csv($spreadsheet);
            } elseif ('xlsx' === $format) {
                $writer = new Xlsx($spreadsheet);
            } else {
                throw new \Exception('Invalid format "'.$format.'"');
            }
            $timezone = $user->getTerritory()?->getTimezone() ?? TimezoneProvider::TIMEZONE_EUROPE_PARIS;
            $datetimeStr = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone($timezone))->format('dmY-Hi');
            $filename = 'utilisateurs-histologe-'.$user->getId().'-'.$datetimeStr.'.'.$format;
            $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;
            $writer->save($tmpFilepath);

            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_USER_EXPORT,
                    to: $user->getEmail(),
                    attachment: $tmpFilepath
                )
            );
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'The export of user failed for the following reason : %s',
                    $exception->getMessage()
                )
            );
        }
    }
}
