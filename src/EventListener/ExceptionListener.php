<?php

namespace App\EventListener;

use App\Entity\Signalement;
use App\Service\NotificationService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

class ExceptionListener
{
    public function __construct(
        private NotificationService $notificationService,
        private ParameterBagInterface $params,
    ) {
    }

    public function onKernelException(ExceptionEvent $event)
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
            $this->notificationService->send(
                NotificationService::TYPE_ERROR_SIGNALEMENT,
                $this->params->get('admin_email'),
                [
                    'url' => $_SERVER['SERVER_NAME'],
                    'code' => $event->getThrowable()->getCode(),
                    'error' => $event->getThrowable()->getMessage(),
                    'req' => $event->getRequest()->getContent(),
                    'signalement' => $event->getRequest()->get('signalement'),
                    'attachment' => $attachment,
                ],
                $territory
            );
        }
    }
}
