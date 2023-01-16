<?php

namespace App\Service;

use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
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
        ) {
    }

    public function dispatch(
        string $nom,
        string $email,
        string $message,
        ) {
        $addNotification = true;

        // add Suivi if signalement with this email for occupant
        $signalementsByOccupants = $this->signalementRepository->findBy([
            'mailOccupant' => $email,
            'closedAt' => null,
        ]);
        if (1 === \count($signalementsByOccupants)) {
            $signalement = $signalementsByOccupants[0];
            $params = [
                'description_contact_form' => nl2br($message).self::MENTION_SENT_BY_EMAIL,
            ];
            $suivi = $this->suiviFactory->createInstanceFrom(
                user: null, // TODO : mettre le bon id occupant
                signalement: $signalement,
                params: $params,
            );
            $this->suiviManager->save($suivi);
            $addNotification = false;
        }

        // add Suivi if signalement with this email for declarant
        $signalementsByDeclarants = $this->signalementRepository->findBy([
            'mailDeclarant' => $email,
            'closedAt' => null,
        ]);
        if (1 === \count($signalementsByDeclarants)) {
            $signalement = $signalementsByDeclarants[0];
            $params = [
                'description_contact_form' => nl2br($message).self::MENTION_SENT_BY_EMAIL,
            ];
            $suivi = $this->suiviFactory->createInstanceFrom(
                user: null, // TODO : mettre le bon id déclarant
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
