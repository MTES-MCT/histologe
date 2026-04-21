<?php

namespace App\Manager;

use App\Entity\FailedEmail;
use App\Service\Mailer\NotificationMail;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\NotificationEmail;

class FailedEmailManager extends AbstractManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        protected ManagerRegistry $managerRegistry,
        string $entityName = FailedEmail::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function create(
        NotificationEmail $message,
        NotificationMail $notificationMail,
        \Throwable $exception,
        string $replyTo,
        bool $notifyUsager,
    ): FailedEmail {
        $failedEmail = (new FailedEmail())
            ->setType($notificationMail->getTypeName())
            ->setToEmail($notificationMail->getEmails())
            ->setFromEmail($message->getFrom()[0]->getAddress() ?? '')
            ->setFromFullname($message->getFrom()[0]->getName())
            ->setReplyTo($replyTo)
            ->setIsRecipientVisible($notificationMail->isRecipientVisible())
            ->setSubject($message->getSubject())
            ->setContext($message->getContext())
            ->setNotifyUsager($notifyUsager)
            ->setErrorMessage($exception->getMessage());

        $this->save($failedEmail);
        $this->entityManager->flush();

        return $failedEmail;
    }
}
