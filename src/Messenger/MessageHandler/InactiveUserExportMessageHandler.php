<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\InactiveUserExportMessage;
use App\Repository\UserRepository;
use App\Service\InactiveUserExportLoader;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\TimezoneProvider;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class InactiveUserExportMessageHandler
{
    public function __construct(
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly LoggerInterface $logger,
        private readonly InactiveUserExportLoader $inactiveUserExportLoader,
        private readonly UserRepository $userRepository,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function __invoke(InactiveUserExportMessage $exportMessage): void
    {
        try {
            $user = $this->userRepository->find($exportMessage->getUserId());
            $format = $exportMessage->getFormat();
            $spreadsheet = $this->inactiveUserExportLoader->load($user);
            if ('csv' === $format) {
                $writer = new Csv($spreadsheet);
            } elseif ('xlsx' === $format) {
                $writer = new Xlsx($spreadsheet);
            } else {
                throw new \Exception('Invalid format "'.$format.'"');
            }
            $timezone = $user->getFirstTerritory()?->getTimezone() ?? TimezoneProvider::TIMEZONE_EUROPE_PARIS;
            $datetimeStr = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone($timezone))->format('dmY-Hi');
            $filename = 'utilisateurs-inactifs-'.$user->getId().'-'.$datetimeStr.'.'.$format;
            $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;
            $writer->save($tmpFilepath);

            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_INACTIVE_USER_EXPORT,
                    to: $user->getEmail(),
                    attachment: $tmpFilepath
                )
            );
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'The export of inactive user failed for the following reason : %s',
                    $exception->getMessage()
                )
            );
        }
    }
}
