<?php

namespace App\Service\Signalement;

use App\Entity\Affectation;
use App\Entity\AutoAffectationRule;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Messenger\InterconnectionBus;
use App\Repository\PartnerRepository;
use App\Specification\Affectation\AllocataireSpecification;
use App\Specification\Affectation\CodeInseeSpecification;
use App\Specification\Affectation\ParcSpecification;
use App\Specification\Affectation\PartnerExcludeSpecification;
use App\Specification\Affectation\PartnerTypeSpecification;
use App\Specification\Affectation\ProcedureSuspecteeSpecification;
use App\Specification\Affectation\ProfilDeclarantSpecification;
use App\Specification\AndSpecification;
use App\Specification\Context\PartnerSignalementContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AutoAssigner
{
    private int $countAffectations;
    private array $assignablePartners = [];
    private array $affectedPartnersNames = [];

    public function __construct(
        private SignalementManager $signalementManager,
        private AffectationManager $affectationManager,
        private SuiviManager $suiviManager,
        private UserManager $userManager,
        private ParameterBagInterface $parameterBag,
        private InterconnectionBus $interconnectionBus,
        private PartnerRepository $partnerRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function assign(Signalement $signalement, $simulation = false): void
    {
        $this->assignablePartners = [];
        $this->countAffectations = 0;
        $autoAffectationRules = $signalement->getTerritory()->getAutoAffectationRules()->filter(function (AutoAffectationRule $autoAffectationRule) {
            return AutoAffectationRule::STATUS_ACTIVE === $autoAffectationRule->getStatus();
        });
        if ($autoAffectationRules->isEmpty()) {
            return;
        }
        if (empty($signalement->getGeoloc())) {
            $logMessage = \sprintf(
                'No auto-affectation for signalement %s - Empty geolocation',
                $signalement->getUuid(),
            );
            $this->logger->info($logMessage);
            \Sentry\captureMessage($logMessage);

            return;
        }
        $adminEmail = $this->parameterBag->get('user_system_email');
        $adminUser = $this->userManager->findOneBy(['email' => $adminEmail]);
        $partners = $this->partnerRepository->findPartnersByLocalization($signalement, $simulation);
        $assignablePartners = [];

        /** @var AutoAffectationRule $rule */
        foreach ($autoAffectationRules as $rule) {
            $specification = new AndSpecification(
                new ProfilDeclarantSpecification($rule->getProfileDeclarant()),
                new PartnerTypeSpecification($rule->getPartnerType()),
                new CodeInseeSpecification($rule->getInseeToInclude(), $rule->getInseeToExclude()),
                new PartnerExcludeSpecification($rule->getPartnerToExclude()),
                new ParcSpecification($rule->getParc()),
                new AllocataireSpecification($rule->getAllocataire()),
                new ProcedureSuspecteeSpecification($rule->getProceduresSuspectees()),
            );

            foreach ($partners as $partner) {
                if ($partner->getIsArchive()) {
                    continue;
                }
                $context = new PartnerSignalementContext($partner, $signalement);
                if ($specification->isSatisfiedBy($context)) {
                    $assignablePartners[$partner->getId()] = $partner;
                }
            }
        }
        $assignablePartners = array_values($assignablePartners);

        if ($simulation) {
            $this->assignablePartners = $assignablePartners;

            return;
        }

        if (!empty($assignablePartners)) {
            $this->activateSignalement($signalement);
            $this->createSuivi($signalement, $adminUser);
            $this->assignPartners($signalement, $adminUser, $assignablePartners);
        }
    }

    private function activateSignalement(Signalement $signalement): void
    {
        $signalement->setStatut(Signalement::STATUS_ACTIVE);
        $signalement->setValidatedAt(new \DateTimeImmutable());
        $this->signalementManager->persist($signalement);
    }

    private function createSuivi(Signalement $signalement, ?User $adminUser): void
    {
        $suivi = $this->suiviManager->createSuivi(
            user: $adminUser,
            signalement: $signalement,
            description: 'Signalement validé',
            type: Suivi::TYPE_AUTO,
            isPublic: true,
            flush: false
        );
        $this->suiviManager->persist($suivi);
    }

    private function assignPartners(Signalement $signalement, ?User $adminUser, array $assignablePartners): void
    {
        /** @var Partner $partner */
        foreach ($assignablePartners as $partner) {
            $affectation = $this->affectationManager->createAffectationFrom(
                $signalement,
                $partner,
                $adminUser
            );
            ++$this->countAffectations;
            $this->affectedPartnersNames[] = $partner->getNom();
            if ($affectation instanceof Affectation) {
                $this->affectationManager->persist($affectation);
                $this->interconnectionBus->dispatch($affectation);
            }
        }
        $this->affectationManager->flush();
    }

    public function getCountAffectations(): int
    {
        return $this->countAffectations;
    }

    public function getAffectedPartnerNames(): array
    {
        return $this->affectedPartnersNames;
    }

    public function getAssignablePartners(): array
    {
        return $this->assignablePartners;
    }
}
