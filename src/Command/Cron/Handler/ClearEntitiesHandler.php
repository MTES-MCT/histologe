<?php

namespace App\Command\Cron\Handler;

use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

readonly class ClearEntitiesHandler
{
    public function __construct(
        private NotificationMailerRegistry $notificationMailerRegistry,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function handle(
        callable $deleteFunction,
        string $entityName,
        string $cronLabel,
    ): int {
        $countDeletedSuccess = $deleteFunction();
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                message: $countDeletedSuccess > 1
                    ? sprintf('%ss ont été supprimés', $entityName)
                    : sprintf('%s a été supprimé', $entityName),
                cronLabel: $cronLabel,
                cronCount: $countDeletedSuccess,
            )
        );

        return $countDeletedSuccess;
    }
}
