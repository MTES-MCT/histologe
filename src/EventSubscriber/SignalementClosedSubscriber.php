<?php

namespace App\EventSubscriber;

use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Event\SignalementClosedEvent;
use App\Manager\SignalementManager;
use App\Service\NotificationService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementClosedSubscriber implements EventSubscriberInterface
{
    public function __construct(private NotificationService $notificationService,
                                private SignalementManager $signalementManager,
                                private UrlGeneratorInterface $urlGenerator,
                                private ParameterBagInterface $parameterBag,
    ) {
    }

    public function onSignalementClosed(SignalementClosedEvent $event): void
    {
        $signalement = $event->getSignalement();

        if (Signalement::STATUS_CLOSED !== $signalement->getStatut()) {
            return;
        }

        $signalement->setCodeSuivi(md5(uniqid()));
        $this->signalementManager->save($signalement);

        $this->notificationService->send(
            NotificationService::TYPE_SIGNALEMENT_CLOSED, [
            $signalement->getMailDeclarant(), $signalement->getMailOccupant(),
        ], [
            'motif_cloture' => MotifCloture::LABEL[$signalement->getMotifCloture()],
            'link' => $this->parameterBag->get('host_url').$this->generateLinkCodeSuivi($signalement->getCodeSuivi()),
        ],
            $signalement->getTerritory()
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SignalementClosedEvent::NAME => 'onSignalementClosed',
        ];
    }

    private function generateLinkCodeSuivi(string $codeSuivi): string
    {
        return $this->urlGenerator->generate('front_suivi_signalement', ['code' => $codeSuivi]);
    }
}
