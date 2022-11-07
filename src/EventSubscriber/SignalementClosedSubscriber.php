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
use Symfony\Component\Security\Core\Security;

class SignalementClosedSubscriber implements EventSubscriberInterface
{
    public function __construct(private NotificationService $notificationService,
                                private SignalementManager $signalementManager,
                                private UrlGeneratorInterface $urlGenerator,
                                private ParameterBagInterface $parameterBag,
                                private Security $security,
    ) {
    }

    public function onSignalementClosed(SignalementClosedEvent $event): void
    {
        $signalement = $event->getSignalement();

        if (Signalement::STATUS_CLOSED !== $signalement->getStatut()) {
            return;
        }

        $signalement
            ->setCodeSuivi(md5(uniqid()))
            ->setClosedAt(new \DateTimeImmutable())
            ->setClosedBy($this->security->getUser());

        $this->signalementManager->save($signalement);

        $this->sendMailToUsager($signalement);
        $this->sendMailToPartners($signalement);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SignalementClosedEvent::NAME => 'onSignalementClosed',
        ];
    }

    private function generateLinkCodeSuivi(string $codeSuivi): string
    {
        return $this->parameterBag->get('host_url').$this->urlGenerator->generate(
            'front_suivi_signalement',
            ['code' => $codeSuivi]
        );
    }

    private function generateLinkSignalementView(string $uuid): string
    {
        return $this->parameterBag->get('host_url').$this->urlGenerator->generate(
            'back_signalement_view',
            ['uuid' => $uuid]
        );
    }

    private function sendMailToUsager(Signalement $signalement): void
    {
        $this->notificationService->send(
            NotificationService::TYPE_SIGNALEMENT_CLOSED_TO_USAGER, [
            $signalement->getMailDeclarant(), $signalement->getMailOccupant(),
        ], [
            'motif_cloture' => MotifCloture::LABEL[$signalement->getMotifCloture()],
            'link' => $this->parameterBag->get('host_url').$this->generateLinkCodeSuivi($signalement->getCodeSuivi()),
        ],
            $signalement->getTerritory()
        );
    }

    private function sendMailToPartners(Signalement $signalement): void
    {
        $usersEmail = $this->signalementManager->getRepository()->findUsersEmailAffectedToSignalement(
            $signalement->getId()
        );

        $this->notificationService->send(
            NotificationService::TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS,
            $usersEmail, [
            'ref_signalement' => $signalement->getReference(),
            'motif_cloture' => MotifCloture::LABEL[$signalement->getMotifCloture()],
            'closed_by' => $signalement->getClosedBy()->getFullname(),
            'partner_name' => $signalement->getClosedBy()->getPartner()->getNom(),
            'link' => $this->generateLinkSignalementView($signalement->getUuid()),
        ],
            $signalement->getTerritory()
        );
    }
}
