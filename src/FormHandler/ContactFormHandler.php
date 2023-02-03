<?php

namespace App\FormHandler;

use App\Entity\Signalement;
use App\Entity\User;
use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Service\NotificationService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ContactFormHandler
{
    public const MENTION_SENT_BY_EMAIL = '<br>Envoyé par email';

    public function __construct(
        private NotificationService $notificationService,
        private ParameterBagInterface $parameterBag,
        private SignalementRepository $signalementRepository,
        private SuiviFactory $suiviFactory,
        private SuiviManager $suiviManager,
        private UserManager $userManager,
    ) {
    }

    public function handle(
        string $nom,
        string $email,
        string $message,
    ) {
        $hasNotificationToSend = true;

        // add Suivi if signalement with this email for occupant or declarant

        $signalementsByOccupants = $this->signalementRepository->findOneOpenedByMailOccupant($email);
        $signalementsByDeclarants = $this->signalementRepository->findOneOpenedByMailDeclarant($email);

        if (null !== $signalementsByOccupants || null !== $signalementsByDeclarants) {
            /** @var Signalement $signalement */
            $signalement = (null !== $signalementsByOccupants)
                ? $signalementsByOccupants
                : $signalementsByDeclarants;
            $params = [
                'description_contact_form' => nl2br($message).self::MENTION_SENT_BY_EMAIL,
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
                );
                $this->suiviManager->save($suivi);
            }
            $hasNotificationToSend = false;
        }

        if ($hasNotificationToSend) {
            $this->notificationService->send(
                NotificationService::TYPE_CONTACT_FORM,
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
