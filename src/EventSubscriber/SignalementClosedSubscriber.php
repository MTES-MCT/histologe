<?php

namespace App\EventSubscriber;

use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Event\SignalementClosedEvent;
use App\Manager\SignalementManager;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Service\Token\TokenGeneratorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class SignalementClosedSubscriber implements EventSubscriberInterface
{
    public function __construct(private NotificationService $notificationService,
                                private SignalementManager $signalementManager,
                                private UserRepository $userRepository,
                                private UrlGeneratorInterface $urlGenerator,
                                private ParameterBagInterface $parameterBag,
                                private TokenGeneratorInterface $tokenGenerator,
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
            ->setCodeSuivi($this->tokenGenerator->generateToken())
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
            'link' => $this->generateLinkCodeSuivi($signalement->getCodeSuivi()),
        ],
            $signalement->getTerritory()
        );
    }

    private function sendMailToPartners(Signalement $signalement): void
    {
        $usersPartnerEmail = $this->signalementManager->getRepository()->findUsersPartnerEmailAffectedToSignalement(
            $signalement->getId()
        );
        $usersAdminEmail = $this->userRepository->findAdminsEmailByTerritory($signalement->getTerritory());
        $sendTo = array_merge($usersPartnerEmail, $usersAdminEmail);

        $this->notificationService->send(
            NotificationService::TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS,
            $sendTo, [
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
