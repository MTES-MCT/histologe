<?php

namespace App\Service;

use App\Entity\Signalement;
use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ContactFormService
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

    public function dispatch(
        string $nom,
        string $email,
        string $message,
        ) {
        $addNotification = true;

        // add Suivi if signalement with this email for occupant or declarant
        $signalementsByOccupants = $this->signalementRepository->findBy([
            'mailOccupant' => $email,
            'closedAt' => null,
        ]);

        $signalementsByDeclarants = $this->signalementRepository->findBy([
            'mailDeclarant' => $email,
            'closedAt' => null,
        ]);

        if (1 === \count($signalementsByOccupants) || 1 === \count($signalementsByDeclarants)) {
            /** @var Signalement $signalement */
            $signalement = (1 === \count($signalementsByOccupants)) ? $signalementsByOccupants[0] : $signalementsByDeclarants[0];
            $params = [
                'description_contact_form' => nl2br($message).self::MENTION_SENT_BY_EMAIL,
            ];
            $userOccupant = $this->userManager->createOccupantAccountFromSignalement($signalement);
            $userDeclarant = $this->userManager->createDeclarantAccountFromSignalement($signalement);

            if (1 === \count($signalementsByOccupants)) {
                $user = $userOccupant;
            } else {
                $user = $userDeclarant;
            }
            $suivi = $this->suiviFactory->createInstanceFrom(
                user: $user,
                signalement: $signalement,
                params: $params,
            );
            $this->suiviManager->save($suivi);
            $addNotification = false;
        }

        // no Suivi added : send classic email
        if ($addNotification) {
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
