<?php

namespace App\EventSubscriber;

use App\Entity\Affectation;
use App\Entity\Enum\MotifCloture;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Event\SignalementClosedEvent;
use App\Factory\SuiviFactory;
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
                                private SuiviFactory $suiviFactory,
                                private Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SignalementClosedEvent::NAME => 'onSignalementClosed',
        ];
    }

    public function onSignalementClosed(SignalementClosedEvent $event): void
    {
        $signalement = $event->getSignalement();
        $affectation = $event->getAffectation();
        $params = $event->getParams();

        if ($signalement instanceof Signalement) {
            $suivi = $this->suiviFactory->createInstance(
                params: $params,
                isPublic: true
            );
            $signalement
                ->setCodeSuivi($this->tokenGenerator->generateToken())
                ->setClosedBy($this->security->getUser())
                ->addSuivi($suivi);
            $this->signalementManager->save($signalement);

            $this->sendMailToUsager($signalement);
            $this->sendMailToPartners($signalement);
        }

        if ($affectation instanceof Affectation) {
            $this->sendMailToPartner(
                signalement: $affectation->getSignalement(),
                partnerToExclude: $this->security->getUser()->getPartner()
            );
        }
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
        $sendTo = $signalement->getMailUsagers();
        if (!empty($sendTo)) {
            $this->notificationService->send(
                NotificationService::TYPE_SIGNALEMENT_CLOSED_TO_USAGER,
                $sendTo,
                [
                    'motif_cloture' => MotifCloture::LABEL[$signalement->getMotifCloture()],
                    'link' => $this->generateLinkCodeSuivi($signalement->getCodeSuivi()),
                ],
                $signalement->getTerritory()
            );
        }
    }

    private function sendMailToPartners(Signalement $signalement): void
    {
        $sendTo = $this->signalementManager->getRepository()->findUsersPartnerEmailAffectedToSignalement(
            $signalement->getId(),
        );

        if (empty($sendTo)) {
            return;
        }

        $this->notificationService->send(
            NotificationService::TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS,
            $sendTo, [
            'ref_signalement' => $signalement->getReference(),
            'motif_cloture' => MotifCloture::LABEL[$signalement->getMotifCloture()],
            'closed_by' => $signalement->getClosedBy()->getNomComplet(),
            'partner_name' => $signalement->getClosedBy()->getPartner()->getNom(),
            'link' => $this->generateLinkSignalementView($signalement->getUuid()),
        ],
            $signalement->getTerritory()
        );
    }

    private function sendMailToPartner(Signalement $signalement, ?Partner $partnerToExclude = null)
    {
        $sendTo = $this->signalementManager->getRepository()->findUsersPartnerEmailAffectedToSignalement(
            $signalement->getId(),
            $partnerToExclude
        );

        if (empty($sendTo)) {
            return;
        }

        $this->notificationService->send(
            NotificationService::TYPE_SIGNALEMENT_CLOSED_TO_PARTNER,
            $sendTo, [
            'ref_signalement' => $signalement->getReference(),
            'partner_name' => $this->security->getUser()->getPartner()->getNom(),
            'link' => $this->generateLinkSignalementView($signalement->getUuid()),
        ],
            $signalement->getTerritory()
        );
    }
}
