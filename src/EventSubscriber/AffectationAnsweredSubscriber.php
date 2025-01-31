<?php

namespace App\EventSubscriber;

use App\Entity\Affectation;
use App\Entity\Suivi;
use App\Event\AffectationAnsweredEvent;
use App\Factory\Interconnection\Idoss\DossierMessageFactory;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Specification\Signalement\FirstAffectationAcceptedSpecification;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class AffectationAnsweredSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SuiviManager $suiviManager,
        private FirstAffectationAcceptedSpecification $firstAcceptedAffectationSpecification,
        private ParameterBagInterface $parameterBag,
        private UserManager $userManager,
        private MessageBusInterface $bus,
        private DossierMessageFactory $dossierMessageFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AffectationAnsweredEvent::NAME => 'onAffectationAnswered',
        ];
    }

    /**
     * @throws ExceptionInterface
     */
    public function onAffectationAnswered(AffectationAnsweredEvent $event): void
    {
        $status = $event->getStatus();
        $affectation = $event->getAffectation();
        $signalement = $affectation->getSignalement();
        $partner = $affectation->getPartner();
        $user = $event->getUser();
        if (Affectation::STATUS_REFUSED == $status) {
            $signalement = $affectation->getSignalement();
            $params = [
                'suivi' => $event->getMessage(),
                'motifRefus' => $event->getMotifRefus(),
            ];
            $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: SuiviManager::buildDescriptionAnswerAffectation($params),
                type: Suivi::TYPE_AUTO,
                user: $user,
            );
        }

        if (Affectation::STATUS_WAIT == $status && !empty($affectation->getHasNotificationUsagerToCreate())) {
            $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: 'Signalement rouvert pour '.mb_strtoupper($partner->getNom()),
                type: Suivi::TYPE_AUTO,
                isPublic: $affectation->getHasNotificationUsagerToCreate(),
                user: $user,
            );
        }
        $this->createFirstAcceptationSpecificationSuivi($event->getAffectation());

        if ($this->dossierMessageFactory->supports($affectation)) {
            $this->bus->dispatch($this->dossierMessageFactory->createInstance($affectation));
        }
    }

    private function createFirstAcceptationSpecificationSuivi(Affectation $affectation): void
    {
        if ($this->firstAcceptedAffectationSpecification->isSatisfiedBy($affectation)) {
            $adminEmail = $this->parameterBag->get('user_system_email');
            $adminUser = $this->userManager->findOneBy(['email' => $adminEmail]);
            $this->suiviManager->createSuivi(
                signalement: $affectation->getSignalement(),
                description: $this->parameterBag->get('suivi_message')['first_accepted_affectation'],
                type: Suivi::TYPE_AUTO,
                isPublic: true,
                user: $adminUser,
                context: Suivi::CONTEXT_NOTIFY_USAGER_ONLY,
            );
        }
    }
}
