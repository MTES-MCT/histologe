<?php

namespace App\Service\DashboardWidget;

use App\Dto\CountPartner;
use App\Dto\CountSignalement;
use App\Dto\CountSuivi;
use App\Dto\CountUser;
use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Partner;
use App\Entity\User;
use App\Repository\AffectationRepository;
use App\Repository\NotificationRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WidgetDataKpiBuilder
{
    private ?CountSignalement $countSignalement = null;
    private ?CountSuivi $countSuivi = null;
    private ?CountUser $countUser = null;
    private ?CountPartner $countPartner = null;
    /**
     * @var array<int, mixed>
     */
    private array $territories = [];
    private ?User $user = null;
    /**
     * @var array<string, mixed>
     */
    private array $parameters;

    /**
     * @var array<string, WidgetCard>
     */
    private array $widgetCards = [];

    public function __construct(
        private readonly WidgetCardFactory $widgetCardFactory,
        private readonly SuiviRepository $suiviRepository,
        private readonly AffectationRepository $affectationRepository,
        private readonly SignalementRepository $signalementRepository,
        private readonly UserRepository $userRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly ParameterBagInterface $parameterBag,
        private readonly Security $security,
        #[Autowire(env: 'FEATURE_SUIVI_ACTION')]
        private readonly bool $featureSuiviAction,
    ) {
    }

    public function createWidgetDataKpiBuilder(): self
    {
        $this->parameters = $this->parameterBag->get('data-kpi')['widgetCards'];
        /** @var User $user */
        $user = $this->security->getUser();
        $this->user = $user;

        return $this;
    }

    /**
     * @param array<int, mixed> $territories
     */
    public function setTerritories(array $territories): self
    {
        $this->territories = $territories;

        return $this;
    }

    /**
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function withCountSignalement(): self
    {
        $this->countSignalement = $this->user->isPartnerAdmin() || $this->user->isUserPartner()
            ? $this->affectationRepository->countSignalementForUser($this->user, $this->territories)
            : $this->signalementRepository->countSignalementByStatus($this->territories);

        $this->countSignalement
            ->setClosedByAtLeastOnePartner($this->notificationRepository->countAffectationClosedNotSeen($this->user, $this->territories))
            ->setAffected($this->affectationRepository->countAffectationForUser($this->user, $this->territories))
            ->setClosedAllPartnersRecently($this->notificationRepository->countSignalementClosedNotSeen($this->user, $this->territories));
        if ($this->featureSuiviAction) {
            $this->countSignalement->setUsagerAbandonProcedure($this->signalementRepository->countSignalementUsagerAbandonProcedure($this->territories));
        }
        if ($this->user->isSuperAdmin() || $this->user->isTerritoryAdmin()) {
            $countSignalementByStatus = $this->signalementRepository->countByStatus(
                territories: $this->territories,
                partners: null,
                qualification: Qualification::NON_DECENCE_ENERGETIQUE,
                qualificationStatuses: [QualificationStatus::NDE_AVEREE, QualificationStatus::NDE_CHECK]
            );
            $newNDE = isset($countSignalementByStatus[SignalementStatus::NEED_VALIDATION->value]) ? $countSignalementByStatus[SignalementStatus::NEED_VALIDATION->value]['count'] : 0;
            $currentNDE = isset($countSignalementByStatus[SignalementStatus::ACTIVE->value]) ? $countSignalementByStatus[SignalementStatus::ACTIVE->value]['count'] : 0;
        } else {
            $countAffectationByStatus = $this->affectationRepository->countByStatusForUser(
                $this->user,
                $this->territories,
                Qualification::NON_DECENCE_ENERGETIQUE,
                [QualificationStatus::NDE_AVEREE, QualificationStatus::NDE_CHECK]
            );
            $newNDE = isset($countAffectationByStatus[Affectation::STATUS_WAIT]) ? $countAffectationByStatus[Affectation::STATUS_WAIT]['count'] : 0;
            $currentNDE = isset($countAffectationByStatus[Affectation::STATUS_ACCEPTED]) ? $countAffectationByStatus[Affectation::STATUS_ACCEPTED]['count'] : 0;
        }

        $this->countSignalement->setNewNDE($newNDE)->setCurrentNDE($currentNDE);

        return $this;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws Exception
     */
    public function withCountSuivi(): self
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $averageSuivi = $this->suiviRepository->getAverageSuivi($this->territories);
        $countSuiviPartner = $this->suiviRepository->countSuiviPartner($this->territories);
        $countSuiviUsager = $this->suiviRepository->countSuiviUsager($this->territories);
        $countSignalementNewSuivi = $this->notificationRepository->countSignalementNewSuivi(
            $user,
            $this->territories
        );
        $countSignalementNoSuivi = $this->suiviRepository->countSignalementNoSuiviSince(
            $this->territories,
            $this->getPartnersFromUser($user)?->map(fn ($partner) => $partner->getId())->toArray() ?? []
        );
        $countSignalementNoSuiviAfter3Relances = $this->suiviRepository->countSignalementNoSuiviAfter3Relances(
            $this->territories,
            $this->getPartnersFromUser($user) ? new ArrayCollection($this->getPartnersFromUser($user)->toArray()) : null
        );

        $this->countSuivi = new CountSuivi(
            $averageSuivi,
            $countSuiviPartner,
            $countSuiviUsager,
            $countSignalementNewSuivi,
            $countSignalementNoSuivi,
            $countSignalementNoSuiviAfter3Relances
        );

        return $this;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function withCountUser(): self
    {
        $this->countUser = $this->userRepository->countUserByStatus($this->territories, $this->user);
        $this->countUser->setArchivingScheduled($this->userRepository->findUsersPendingToArchive($this->user, $this->territories, true));

        return $this;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function withCountPartner(): self
    {
        $this->countPartner = $this->partnerRepository->countPartnerNonNotifiables($this->territories);

        return $this;
    }

    /**
     * @param array<string, mixed> $linkParameters
     */
    public function addWidgetCard(string $key, ?int $count = null, array $linkParameters = []): self
    {
        if ($this->canAddCard($key)) {
            $widgetParams = $this->parameters[$key];
            $link = $widgetParams['link'] ?? null;
            $label = $widgetParams['label'] ?? null;
            $widgetParams['params']['territoire'] = 1 === count($this->territories) ? reset($this->territories)->getId() : null;
            if (!in_array($key, ['cardPartenairesNonNotifiables', 'cardArchivingScheduledUsers'])) {
                $widgetParams['params']['isImported'] = 'oui';
            } else {
                /** @var User $user */
                $user = $this->security->getUser();
                if (0 === count($this->territories) || $user->isTerritoryAdmin()) {
                    unset($widgetParams['params']['territoire']);
                }
            }
            $parameters = array_merge($linkParameters, $widgetParams['params'] ?? []);
            $widgetCard = $this->widgetCardFactory->createInstance($label, $count, $link, $parameters);
            if (!$this->hasWidgetCard($key)) {
                $this->widgetCards[$key] = $widgetCard;
            }
        }

        return $this;
    }

    private function canAddCard(string $key): bool
    {
        $roles = $this->user->getRoles();
        $role = array_shift($roles);
        if (\in_array($role, $this->parameters[$key]['roles'])) {
            if (isset($this->parameters[$key]['params']['nde']) && '1' === $this->parameters[$key]['params']['nde']) {
                return $this->security->isGranted(UserVoter::SEE_NDE, $this->user);
            }

            return true;
        }

        return false;
    }

    public function hasWidgetCard(string $key): bool
    {
        return \in_array($key, $this->widgetCards);
    }

    public function build(): WidgetDataKpi
    {
        $partnerIds = [];
        if ($this->user) {
            foreach ($this->user->getPartners() as $partner) {
                $partnerIds[] = $partner->getId();
            }
        }

        $this
            ->addWidgetCard('cardNouveauxSignalements', $this->countSignalement->getNew())
            ->addWidgetCard('cardCloturesPartenaires', $this->countSignalement->getClosedByAtLeastOnePartner())
            ->addWidgetCard('cardMesAffectations', null, ['partenaires' => $partnerIds])
            ->addWidgetCard('cardTousLesSignalements', $this->countSignalement->getTotal())
            ->addWidgetCard('cardCloturesGlobales', $this->countSignalement->getClosedAllPartnersRecently())
            ->addWidgetCard('cardNouvellesAffectations', $this->countSignalement->getNew())
            ->addWidgetCard('cardSignalementsNouveauxNonDecence', $this->countSignalement->getNewNDE())
            ->addWidgetCard('cardSignalementsEnCoursNonDecence', $this->countSignalement->getCurrentNDE())
            ->addWidgetCard('cardNouveauxSuivis', $this->countSuivi->getSignalementNewSuivi())
            ->addWidgetCard('cardSansSuivi', $this->countSuivi->getSignalementNoSuivi())
            ->addWidgetCard('cardNoSuiviAfter3Relances', $this->countSuivi->getNoSuiviAfter3Relances())
            ->addWidgetCard('cardPartenairesNonNotifiables', $this->countPartner->getNonNotifiables())
            ->addWidgetCard('cardArchivingScheduledUsers', $this->countUser->getArchivingScheduled());

        if ($this->featureSuiviAction) {
            $this->addWidgetCard('cardUsagerAbandonProcedure', $this->countSignalement->getUsagerAbandonProcedure());
        }

        return new WidgetDataKpi(
            $this->widgetCards,
            $this->countSignalement,
            $this->countSuivi,
            $this->countUser,
            $this->countPartner,
        );
    }

    /**
     * @return Collection<int, Partner>|null
     */
    private function getPartnersFromUser(User $user): ?Collection
    {
        return 1 === \count(array_diff([User::ROLE_USER_PARTNER, User::ROLE_ADMIN_PARTNER], $user->getRoles())) ? $user->getPartners() : null;
    }
}
