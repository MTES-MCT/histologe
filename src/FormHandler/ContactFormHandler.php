<?php

namespace App\FormHandler;

use App\Entity\Signalement;
use App\Entity\User;
use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ContactFormHandler
{
    public const MENTION_SENT_BY_EMAIL = '<br>Envoyé par email';

    public function __construct(
        private NotificationMailerRegistry $notificationMailerRegistry,
        private ParameterBagInterface $parameterBag,
        private SignalementRepository $signalementRepository,
        private SuiviFactory $suiviFactory,
        private SuiviManager $suiviManager,
        private UserManager $userManager,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(
        string $nom,
        string $email,
        string $message,
        string $organisme,
        string $objet
    ) {
        $hasNotificationToSend = true;

        // add Suivi if signalement with this email for occupant or declarant

        try {
            $signalementsByOccupants = $this->signalementRepository->findOneOpenedByMailOccupant($email);
            $signalementsByDeclarants = $this->signalementRepository->findOneOpenedByMailDeclarant($email);
        } catch (NonUniqueResultException $exception) {
            $signalementsByOccupants = $signalementsByDeclarants = null;
            $this->logger->error($exception->getMessage());
        }
        if (null !== $signalementsByOccupants || null !== $signalementsByDeclarants) {
            /** @var Signalement $signalement */
            $signalement = (null !== $signalementsByOccupants)
                ? $signalementsByOccupants
                : $signalementsByDeclarants;
            $params = [
                'description' => nl2br(strip_tags($message)).self::MENTION_SENT_BY_EMAIL,
            ];
            $userOccupant = $this->userManager->createUsagerFromSignalement($signalement, $this->userManager::OCCUPANT);
            $userDeclarant = $this->userManager->createUsagerFromSignalement($signalement, $this->userManager::DECLARANT);

            /** @var User $user */
            $user = (null !== $signalementsByOccupants)
                ? $userOccupant
                : $userDeclarant;

            if (null !== $user) {
                $suivi = $this->suiviFactory->createInstanceFrom(
                    user: $user,
                    signalement: $signalement,
                    params: $params,
                    isPublic: true
                );
                $this->suiviManager->save($suivi);
            }
            $hasNotificationToSend = false;
        }

        if ($hasNotificationToSend) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CONTACT_FORM,
                    to: $this->parameterBag->get('contact_email'),
                    fromEmail: $email,
                    fromFullname: $nom,
                    message: nl2br(strip_tags($message)),
                    params: [
                        'organisme' => $organisme,
                        'objet' => $objet,
                    ]
                )
            );
        }
    }
}
