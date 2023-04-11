<?php

namespace App\FormHandler;

use App\Entity\Signalement;
use App\Entity\User;
use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Service\Mailer\NotificationMailer;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ContactFormHandler
{
    public const MENTION_SENT_BY_EMAIL = '<br>Envoyé par email';

    public function __construct(
        private NotificationMailer $notificationService,
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
                'description' => nl2br($message).self::MENTION_SENT_BY_EMAIL,
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
            $this->notificationService->send(
                NotificationMailer::TYPE_CONTACT_FORM,
                $this->parameterBag->get('contact_email'),
                [
                    'nom' => $nom,
                    'mail' => $email,
                    'reply' => $email,
                    'message' => nl2br($message),
                ],
                null
            );
        }
    }
}
