<?php

namespace App\Service\Mailer\Mail;

use App\Entity\FailedEmail;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sentry\State\Scope;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractNotificationMailer implements NotificationMailerInterface
{
    protected ?NotificationMailerType $mailerType = null;
    protected ?string $mailerSubject = null;
    protected ?string $mailerButtonText = null;
    protected ?string $mailerTemplate = null;
    protected ?string $tagHeader = null;
    protected array $mailerParams = [];

    public function __construct(
        protected MailerInterface $mailer,
        protected ParameterBagInterface $parameterBag,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function send(NotificationMail $notificationMail, bool $saveFailedMail = true): bool
    {
        if (!$this->parameterBag->get('mail_enable')) {
            $this->logger->info('E-mail has been disable, please enable MAIL_ENABLE=1');

            return true;
        }
        $territory = $notificationMail->getTerritory();
        $this->mailerParams = $this->getMailerParamsFromNotification($notificationMail);
        $this->updateMailerSubjectFromNotification($notificationMail);
        $params = [
            'template' => $this->mailerTemplate,
            'subject' => $this->mailerSubject,
            'btntext' => $this->mailerButtonText,
            'url' => $this->parameterBag->get('host_url'),
        ];

        $params = array_merge($params, $notificationMail->getParams(), $this->mailerParams);
        $message = $this->renderMailContentWithParams($params, $territory ?? null);

        if (null !== $this->tagHeader) {
            $message->getHeaders()->add(new TagHeader($this->tagHeader));
        }

        $territoryName = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC')
            ->transliterate(
                (!empty($territory) && null !== $territory->getName())
                    ? $territory->getName()
                    : 'ALERTE'
            );

        foreach ($notificationMail->getEmails() as $email) {
            try {
                $email && $message->addTo($email);
            } catch (\Exception $e) {
                $this->logger->error(\sprintf('[%s] %s', $notificationMail->getType()->name, $e->getMessage()));
            }
        }

        $message->from(
            new Address(
                $this->parameterBag->get('notifications_email'),
                mb_strtoupper($this->parameterBag->get('platform_name').' - '.$territoryName)
            )
        );

        if (!empty($params['attach'])) {
            if (\is_array($params['attach'])) {
                foreach ($params['attach'] as $attachPath) {
                    $message->attachFromPath($attachPath);
                }
            } else {
                $message->attachFromPath($params['attach']);
            }
        }
        if (!empty($params['attachContent'])) {
            $message->attach($params['attachContent']['content'], $params['attachContent']['filename']);
        }
        try {
            $this->mailer->send($message);

            return true;
        } catch (\Throwable $exception) {
            $this->logAndSaveFailedEmail($notificationMail, $exception, $params, $saveFailedMail);
        }

        return false;
    }

    private function logAndSaveFailedEmail(NotificationMail $notificationMail, \Throwable $exception, array $params, bool $saveFailedMail): void
    {
        $object = $params['entity'] ?? null;
        $notifyUsager = false;
        if ($object instanceof Suivi) {
            $notifyUsager = $object->getIsPublic() ? true : false;
        } elseif (str_contains($this->mailerType->name, 'USAGER')) {
            $notifyUsager = true;
        }
        \Sentry\configureScope(function (Scope $scope) use ($notifyUsager): void {
            $scope->setTag('mailer_type', $this->mailerType->name);
            $scope->setTag('notify_usager', $notifyUsager ? 'yes' : 'no');
        });
        $this->logger->error(\sprintf(
            '[%s] %s',
            $notificationMail->getType()->name, $exception->getMessage()
        ));
        if ($saveFailedMail && NotificationMailerType::TYPE_ERROR_SIGNALEMENT !== $notificationMail->getType()) {
            $failedEmail = new FailedEmail();
            $failedEmail->setType($notificationMail->getTypeName());
            $failedEmail->setToEmail($notificationMail->getEmails()); // TODO : getTo ?
            $failedEmail->setFromEmail($notificationMail->getFromEmail());
            $failedEmail->setFromFullname($notificationMail->getFromFullname());
            $failedEmail->setSignalement($notificationMail->getSignalement());
            $failedEmail->setSignalementDraft($notificationMail->getSignalementDraft());
            $failedEmail->setSuivi($notificationMail->getSuivi());
            $failedEmail->setMessage($notificationMail->getMessage());
            $failedEmail->setTerritory($notificationMail->getTerritory());
            $failedEmail->setUser($notificationMail->getUser());
            $failedEmail->setIntervention($notificationMail->getIntervention());
            $failedEmail->setPreviousVisiteDate($notificationMail->getPreviousVisiteDate());
            $failedEmail->setAttachment(
                \is_array($notificationMail->getAttachment()) ?
                $notificationMail->getAttachment() : [$notificationMail->getAttachment()]);
            $failedEmail->setMotif($notificationMail->getMotif());
            $failedEmail->setCronLabel($notificationMail->getCronLabel());
            $failedEmail->setCronCount($notificationMail->getCronCount());
            $failedEmail->setParams($notificationMail->getParams());
            $failedEmail->setNotifyUsager($notifyUsager);
            $failedEmail->setErrorMessage($exception->getMessage());
            $failedEmail->setCreatedAt(new \DateTimeImmutable());

            try {
                $this->entityManager->persist($failedEmail);
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $this->logger->error('Failed to save FailedEmail: '.$e->getMessage());
            }
        }
    }

    public function supports(NotificationMailerType $type): bool
    {
        return $this->mailerType === $type;
    }

    public function generateLinkSignalementView(string $uuid): string
    {
        return $this->parameterBag->get('host_url').$this->urlGenerator->generate(
            'back_signalement_view',
            ['uuid' => $uuid]
        );
    }

    public function generateLink(string $route, array $params): string
    {
        return $this->parameterBag->get('host_url').$this->urlGenerator->generate($route, $params);
    }

    public function generateAbsoluteLink(string $route, array $params): string
    {
        return $this->urlGenerator->generate(
            $route,
            $params,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function updateMailerSubjectFromNotification(NotificationMail $notificationMail): void
    {
    }

    abstract public function getMailerParamsFromNotification(NotificationMail $notificationMail): array;

    private function renderMailContentWithParams(
        array $params,
        ?Territory $territory,
    ): NotificationEmail {
        $config['territory'] = $territory;
        $notification = new NotificationEmail();
        $notification->markAsPublic();

        return $notification->htmlTemplate('emails/'.$this->mailerTemplate.'.html.twig')
            ->context(array_merge($params, $config))
            ->replyTo($this->parameterBag->get('reply_to_email'))
            ->subject(
                mb_strtoupper($this->parameterBag->get('platform_name'))
                .' '
                .mb_strtoupper((!empty($territory) && null !== $territory->getName()) ? $territory->getName() : 'ALERTE')
                .' - '.
                str_replace('%param.platform_name%', $this->parameterBag->get('platform_name'), $this->mailerSubject)
            );
    }
}
