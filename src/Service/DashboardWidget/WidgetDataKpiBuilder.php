<?php

namespace App\Service\DashboardWidget;

use App\Dto\CountSignalement;
use App\Dto\CountSuivi;
use App\Dto\CountUser;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\AffectationRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

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
        private ParameterBagInterface $parameterBag,
        private Security $security,
    ) {
    }

    public function createWidgetDataKpiBuilder(): self
    {
        $this->parameters = $this->parameterBag->get('data-kpi')['widget_cards'];
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
        $this->countSignalement = $this->signalementRepository->countSignalementByStatus($this->territory);

        $this->countSignalement->setClosedByAtLeastOnePartner(
            $this->signalementRepository->countSignalementClosedByAtLeast(1, $this->territory)
        )
            ->setAffected(
                $this->affectationRepository->countAffectationByPartner($this->user->getPartner())
            );

        return $this;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws Exception
     */
    public function withCountSuivi(): self
    {
        $averageSuivi = $this->suiviRepository->getAverageSuivi($this->territory);
        $countSuiviPartner = $this->suiviRepository->countSuiviPartner($this->territory);
        $countSuiviUsager = $this->suiviRepository->countSuiviUsager($this->territory);

        $this->countSuivi = new CountSuivi($averageSuivi, $countSuiviPartner, $countSuiviUsager);

        return $this;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function withCountUser(): self
    {
        $this->countUser = $this->userRepository->countUserByStatus($this->territory);

        return $this;
    }

    public function addWidgetCard(string $key, ?int $count = null, array $linkParameters = []): self
    {
        $roles = $this->user->getRoles();
        $role = array_shift($roles);
        if (\in_array($role, $this->parameters[$key]['roles'])) {
            $widgetParams = $this->parameters[$key];
            $link = $widgetParams['link'] ?? null;
            $label = $widgetParams['label'] ?? null;
            $parameters = array_merge($linkParameters, $widgetParams['params'] ?? []);
            $widgetCard = $this->widgetCardFactory->createInstance($label, $count, $link, $parameters);
            $this->widgetCards[$key] = $widgetCard;
        }

        return $this;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @todo: card_nouveaux_suivis and card_sans_suivi
     * https://github.com/MTES-MCT/histologe/issues/867
     * https://github.com/MTES-MCT/histologe/issues/869
     */
    public function build(): WidgetDataKpi
    {
        $this
            ->addWidgetCard('card_nouveaux_signalements', $this->countSignalement->getNew())
            ->addWidgetCard('card_clotures_partenaires', $this->countSignalement->getClosedByAtLeastOnePartner())
            ->addWidgetCard('card_mes_affectations')
            ->addWidgetCard('card_tous_les_signalements', $this->countSignalement->getTotal())
            ->addWidgetCard('card_clotures_globales', $this->countSignalement->getClosed())
            ->addWidgetCard('card_nouvelles_affectations');

        return new WidgetDataKpi(
            $this->widgetCards,
            $this->countSignalement,
            $this->countSuivi,
            $this->countUser,
        );
    }
}
