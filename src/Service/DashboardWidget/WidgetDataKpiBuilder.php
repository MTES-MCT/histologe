<?php

namespace App\Service\DashboardWidget;

use App\Dto\CountSignalement;
use App\Dto\CountSuivi;
use App\Dto\CountUser;
use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\AffectationRepository;
use App\Repository\NotificationRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WidgetDataKpiBuilder
{
    private ?CountSignalement $countSignalement = null;
    private ?CountSuivi $countSuivi = null;
    private ?CountUser $countUser = null;
    private ?Territory $territory = null;
    private ?User $user = null;
    private array $parameters;

    /** @var WidgetCard[] */
    private array $widgetCards = [];

    public function __construct(
        private WidgetCardFactory $widgetCardFactory,
        private SuiviRepository $suiviRepository,
        private AffectationRepository $affectationRepository,
        private SignalementRepository $signalementRepository,
        private UserRepository $userRepository,
        private NotificationRepository $notificationRepository,
        private ParameterBagInterface $parameterBag,
        private Security $security,
    ) {
    }

    public function createWidgetDataKpiBuilder(): self
    {
        $this->parameters = $this->parameterBag->get('data-kpi')['widgetCards'];
        $this->user = $this->security->getUser();

        return $this;
    }

    public function setTerritory(?Territory $territory = null): self
    {
        $this->territory = $territory;

        return $this;
    }

    /**
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws Exception
     */
    public function withCountSignalement(): self
    {
        $this->countSignalement = $this->user->isPartnerAdmin() || $this->user->isUserPartner()
            ? $this->affectationRepository->countSignalementByPartner($this->user->getPartner())
            : $this->signalementRepository->countSignalementByStatus($this->territory);

        $this->countSignalement
            ->setClosedByAtLeastOnePartner($this->notificationRepository->countAffectationClosedNotSeen($this->user, $this->territory))
            ->setAffected($this->affectationRepository->countAffectationByPartner($this->user->getPartner()))
            ->setClosedAllPartnersRecently($this->notificationRepository->countSignalementClosedNotSeen($this->user, $this->territory));

        if ($this->user->isSuperAdmin() || $this->user->isTerritoryAdmin()) {
            $countSignalementByStatus = $this->signalementRepository->countByStatus($this->territory, null, false, Qualification::NON_DECENCE_ENERGETIQUE);
            $newNDE = isset($countSignalementByStatus[Signalement::STATUS_NEED_VALIDATION]) ? $countSignalementByStatus[Signalement::STATUS_NEED_VALIDATION]['count'] : 0;
            $currentNDE = isset($countSignalementByStatus[Signalement::STATUS_ACTIVE]) ? $countSignalementByStatus[Signalement::STATUS_ACTIVE]['count'] : 0;
            $this->countSignalement
                ->setNewNDE($newNDE)
                ->setCurrentNDE($currentNDE);
        } else {
            $countAffectationByStatus = $this->affectationRepository->countByStatusForUser($this->user, $this->territory, Qualification::NON_DECENCE_ENERGETIQUE);
            $newNDE = isset($countAffectationByStatus[Affectation::STATUS_WAIT]) ? $countAffectationByStatus[Affectation::STATUS_WAIT]['count'] : 0;
            $currentNDE = isset($countAffectationByStatus[Affectation::STATUS_ACCEPTED]) ? $countAffectationByStatus[Affectation::STATUS_ACCEPTED]['count'] : 0;

            $this->countSignalement
                ->setNewNDE($newNDE)
                ->setCurrentNDE($currentNDE);
        }

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
        $averageSuivi = $this->suiviRepository->getAverageSuivi($this->territory);
        $countSuiviPartner = $this->suiviRepository->countSuiviPartner($this->territory);
        $countSuiviUsager = $this->suiviRepository->countSuiviUsager($this->territory);
        $countSignalementNewSuivi = $this->notificationRepository->countSignalementNewSuivi(
            $user,
            $this->territory
        );
        $countSignalementNoSuivi = $this->suiviRepository->countSignalementNoSuiviSince(
            Suivi::DEFAULT_PERIOD_INACTIVITY,
            $this->territory,
            $this->getPartnerFromUser($user)
        );

        $this->countSuivi = new CountSuivi(
            $averageSuivi,
            $countSuiviPartner,
            $countSuiviUsager,
            $countSignalementNewSuivi,
            $countSignalementNoSuivi
        );

        return $this;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function withCountUser(): self
    {
        $this->countUser = $this->userRepository->countUserByStatus($this->territory, $this->user);

        return $this;
    }

    public function addWidgetCard(string $key, ?int $count = null, array $linkParameters = []): self
    {
        if ($this->canAddCard($key)) {
            $widgetParams = $this->parameters[$key];
            $link = $widgetParams['link'] ?? null;
            $label = $widgetParams['label'] ?? null;
            $widgetParams['params']['territoire_id'] = $this->territory?->getId();
            $parameters = array_merge($linkParameters, $widgetParams['params'] ?? []);
            $widgetCard = $this->widgetCardFactory->createInstance($label, $count, $link, $parameters);
            if (!$this->hasWidgetCard($key)) {
                $this->widgetCards[$key] = $widgetCard;
            }
        }

        return $this;
    }

    private function canAddCard($key)
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
        $this
            ->addWidgetCard('cardNouveauxSignalements', $this->countSignalement->getNew())
            ->addWidgetCard('cardCloturesPartenaires', $this->countSignalement->getClosedByAtLeastOnePartner())
            ->addWidgetCard('cardMesAffectations')
            ->addWidgetCard('cardTousLesSignalements', $this->countSignalement->getTotal())
            ->addWidgetCard('cardCloturesGlobales', $this->countSignalement->getClosedAllPartnersRecently())
            ->addWidgetCard('cardNouvellesAffectations', $this->countSignalement->getNew())
            ->addWidgetCard('cardSignalementsNouveauxNonDecence', $this->countSignalement->getNewNDE())
            ->addWidgetCard('cardSignalementsEnCoursNonDecence', $this->countSignalement->getCurrentNDE())
            ->addWidgetCard('cardNouveauxSuivis', $this->countSuivi->getSignalementNewSuivi())
            ->addWidgetCard('cardSansSuivi', $this->countSuivi->getSignalementNoSuivi());

        return new WidgetDataKpi(
            $this->widgetCards,
            $this->countSignalement,
            $this->countSuivi,
            $this->countUser,
        );
    }

    private function getPartnerFromUser(User $user): ?Partner
    {
        return 1 === \count(array_diff([User::ROLE_USER_PARTNER, User::ROLE_ADMIN_PARTNER], $user->getRoles()))
            ? $user->getPartner()
            : null;
    }
}
