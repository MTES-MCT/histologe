<?php

namespace App\EventSubscriber;

use App\Entity\Affectation;
use App\Entity\Enum\MotifCloture;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Event\SignalementClosedEvent;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Service\Token\TokenGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementClosedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationService $notificationService,
        private SignalementManager $signalementManager,
        private UserRepository $userRepository,
        private UrlGeneratorInterface $urlGenerator,
        private ParameterBagInterface $parameterBag,
        private TokenGeneratorInterface $tokenGenerator,
        private SuiviManager $suiviManager,
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
        $user = $this->security->getUser();

        if ($signalement instanceof Signalement) {
            $suivi = $this->suiviManager->createSuivi(
                $user,
                $signalement,
                params: $params,
                isPublic: true
            );
            $signalement
                ->setCodeSuivi($this->tokenGenerator->generateToken())
                ->setClosedBy($user)
                ->addSuivi($suivi);

            $this->sendMailToUsager($signalement);
            $this->sendMailToPartners($signalement);
        }

        if ($affectation instanceof Affectation) {
            $signalement = $affectation->getSignalement();
            $suivi = $this->suiviManager->createSuivi($user, $signalement, $params);
            $signalement->addSuivi($suivi);
            $this->sendMailToPartner(
                signalement: $signalement,
                partnerToExclude: $this->security->getUser()->getPartner()
            );
        }

        $this->signalementManager->save($signalement);
    }

    private function generateLinkCodeSuivi(string $codeSuivi, string $email): string
    {
        return $this->parameterBag->get('host_url').$this->urlGenerator->generate(
            'front_suivi_signalement',
            [
                'code' => $codeSuivi,
                'from' => $email,
            ]
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
        $toRecipients = $signalement->getMailUsagers();
        if (!empty($toRecipients)) {
            foreach ($toRecipients as $toRecipient) {
                $this->notificationService->send(
                    NotificationService::TYPE_SIGNALEMENT_CLOSED_TO_USAGER,
                    [$toRecipient],
                    [
                        'motif_cloture' => MotifCloture::LABEL[$signalement->getMotifCloture()],
                        'link' => $this->generateLinkCodeSuivi($signalement->getCodeSuivi(), $toRecipient),
                    ],
                    $signalement->getTerritory()
                );
            }
        }
    }

    private function sendMailToPartners(Signalement $signalement): void
    {
        $sendTo = $this->signalementManager->findEmailsAffectedToSignalement($signalement);
        if (empty($sendTo)) {
            return;
        }

        $this->notificationService->send(
            NotificationService::TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS,
            $sendTo,
            [
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
            $sendTo,
            [
                'ref_signalement' => $signalement->getReference(),
                'partner_name' => $this->security->getUser()->getPartner()->getNom(),
                'link' => $this->generateLinkSignalementView($signalement->getUuid()),
            ],
            $signalement->getTerritory()
        );
    }
}
