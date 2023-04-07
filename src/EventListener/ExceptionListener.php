<?php

namespace App\EventListener;

use App\Entity\Signalement;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

class ExceptionListener
{
    public function __construct(
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $params,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof MethodNotAllowedException &&
            null !== $event->getRequest()->get('signalement')) {
            $attachment = ['documents' => 0, 'photos' => 0];
            if ($files = $event->getRequest()->files->get('signalement')) {
                foreach ($files as $k => $file) {
                    foreach ($file as $file_) {
                        ++$attachment[$k];
                    }
                }
            }

            $territory = null;
            if ($event->getRequest()->get('signalement') instanceof Signalement) {
                $territory = $event->getRequest()->get('signalement')->getTerritory();
            }
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_ERROR_SIGNALEMENT,
                    to: $this->params->get('admin_email'),
                    territory: $territory,
                    event: $event,
                    attachment: $attachment,
                )
            );
        }
    }
}
