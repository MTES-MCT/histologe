<?php

namespace App\Service\Signalement;

use App\Entity\AutoAffectationRule;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\UserManager;
use App\Manager\UserSignalementSubscriptionManager;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use App\Service\NotificationAndMailSender;
use App\Specification\Affectation\AllocataireSpecification;
use App\Specification\Affectation\CodeInseeSpecification;
use App\Specification\Affectation\ParcSpecification;
use App\Specification\Affectation\PartnerExcludeSpecification;
use App\Specification\Affectation\PartnerTypeSpecification;
use App\Specification\Affectation\ProcedureSuspecteeSpecification;
use App\Specification\Affectation\ProfilDeclarantSpecification;
use App\Specification\AndSpecification;
use App\Specification\Context\PartnerSignalementContext;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;

class AutoAssigner
{
    private int $countAffectations;
    /** @var array<string> */
    private array $affectedPartnersNames = [];

    public function __construct(
        private readonly SignalementManager $signalementManager,
        private readonly AffectationManager $affectationManager,
        private readonly UserManager $userManager,
        private readonly PartnerRepository $partnerRepository,
        private readonly UserRepository $userRepository,
        private readonly UserSignalementSubscriptionManager $subscriptionManager,
        private readonly NotificationAndMailSender $notificationAndMailSender,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function assignOrSendNewSignalementNotification(Signalement $signalement): void
    {
        $hasAssignablePartners = $this->assign($signalement, true);
        if (count($hasAssignablePartners)) {
            $this->assign($signalement);
        } else {
            $this->notificationAndMailSender->sendNewSignalement($signalement);
        }
    }

    /**
     * @return array<int, Partner>
     *
     * @throws ExceptionInterface|Exception
     */
    public function assign(Signalement $signalement, bool $simulation = false): array
    {
        $this->countAffectations = 0;
        $autoAffectationRules = $signalement->getTerritory()->getAutoAffectationRules()->filter(function (AutoAffectationRule $autoAffectationRule) {
            return AutoAffectationRule::STATUS_ACTIVE === $autoAffectationRule->getStatus();
        });
        if ($autoAffectationRules->isEmpty()) {
            return [];
        }
        if (empty($signalement->getGeoloc())) {
            $logMessage = \sprintf(
                'No auto-affectation for signalement %s - Empty geolocation',
                $signalement->getUuid(),
            );
            $this->logger->info($logMessage);
            \Sentry\captureMessage($logMessage);

            return [];
        }
        $adminUser = $this->userManager->getSystemUser();
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

        if (!$simulation && !empty($assignablePartners)) {
            $this->signalementManager->activateSignalementAndCreateFirstSuivi($signalement, $adminUser);
            $this->assignPartners($signalement, $adminUser, $assignablePartners);
            $this->subscribeTerritoryAdmins($signalement, $adminUser);
        }

        return $assignablePartners;
    }

    /**
     * @param array<Partner> $assignablePartners
     *
     * @throws ExceptionInterface
     */
    private function assignPartners(Signalement $signalement, ?User $adminUser, array $assignablePartners): void
    {
        foreach ($assignablePartners as $partner) {
            $affectation = $this->affectationManager->createAffectationFrom(
                $signalement,
                $partner,
                $adminUser
            );
            if (is_bool($affectation)) {
                continue;
            }
            $signalement->addAffectation($affectation);
            ++$this->countAffectations;
            $this->affectedPartnersNames[] = $partner->getNom();
        }
        $this->affectationManager->flush();
    }

    private function subscribeTerritoryAdmins(Signalement $signalement, ?User $adminUser): void
    {
        $territoryAdmins = $this->userRepository->findActiveTerritoryAdmins(
            territoryId: $signalement->getTerritory()->getId(),
        );

        foreach ($territoryAdmins as $territoryAdmin) {
            $this->subscriptionManager->createOrGet(
                userToSubscribe: $territoryAdmin,
                signalement: $signalement,
                createdBy: $adminUser
            );
        }

        $this->subscriptionManager->flush();
    }

    public function getCountAffectations(): int
    {
        return $this->countAffectations;
    }

    /**
     * @return array<string>
     */
    public function getAffectedPartnerNames(): array
    {
        return $this->affectedPartnersNames;
    }
}
