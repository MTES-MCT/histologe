<?php

namespace App\Manager;

use App\Dto\CountSignalement;
use App\Dto\CountSuivi;
use App\Dto\CountUser;
use App\Entity\Territory;
use App\Repository\AffectationRepository;
use App\Repository\JobEventRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WidgetDataManager
{
    public function __construct(
        private SignalementRepository $signalementRepository,
        private JobEventRepository $jobEventRepository,
        private AffectationRepository $affectationRepository,
        private UserRepository $userRepository,
        private SuiviRepository $suiviRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function countSignalementAcceptedNoSuivi(Territory $territory): array
    {
        return $this->signalementRepository->countSignalementAcceptedNoSuivi($territory);
    }

    /**
     * @throws Exception
     */
    public function getCountSignalementsByTerritory(): array
    {
        $countSignalementTerritoryList = $this->signalementRepository->countSignalementTerritory();

        return array_map(function ($countSignalementTerritoryItem) {
            $countSignalementTerritoryItem['new'] = (int) $countSignalementTerritoryItem['new'];
            $countSignalementTerritoryItem['no_affected'] = (int) $countSignalementTerritoryItem['no_affected'];

            return $countSignalementTerritoryItem;
        }, $countSignalementTerritoryList);
    }

    public function countAffectationPartner(?Territory $territory = null): array
    {
        $countAffectationPartnerList = $this->affectationRepository->countAffectationPartner($territory);

        return array_map(function ($countAffectationPartnerItem) {
            $countAffectationPartnerItem['waiting'] = (int) $countAffectationPartnerItem['waiting'];
            $countAffectationPartnerItem['refused'] = (int) $countAffectationPartnerItem['refused'];

            return $countAffectationPartnerItem;
        }, $countAffectationPartnerList);
    }

    /**
     * @throws Exception
     */
    public function findLastJobEventByType(string $type): array
    {
        return $this->jobEventRepository->findLastJobEventByType($type);
    }

    /**
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countDataKpi(?Territory $territory = null): array
    {
        $countSignalement = $this->countSignalementData($territory);

        return [
            'count_signalement' => $countSignalement,
            'count_suivi' => $this->countSuiviData($territory),
            'count_user' => $this->countUserData($territory),
            'card_widget' => [
                'new_signalement' => [
                    'count' => $countSignalement->getNew(),
                    'link' => $this->urlGenerator->generate('back_index', [
                        'status_signalement' => 'new',
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                ],
                'new_suivi' => [
                    'count' => 0,
                    'link' => '',
                ],
                'no_suivi' => [
                    'count' => 0,
                    'link' => '',
                ],
                'closed_signalement_partners' => [
                    'count' => 0,
                    'link' => '',
                ],
                'affectation' => [
                    'link' => '',
                ],
            ],
        ];
    }

    /**
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countSignalementData(?Territory $territory = null): CountSignalement
    {
        return $this->signalementRepository->countSignalementByStatus($territory);
    }

    public function countSuiviData(?Territory $territory = null): CountSuivi
    {
        $averageSuivi = $this->suiviRepository->getAverageSuivi($territory);
        $countSuiviPartner = $this->suiviRepository->countSuiviPartner($territory);
        $countSuiviUsager = $this->suiviRepository->countSuiviUsager($territory);

        return new CountSuivi($averageSuivi, $countSuiviPartner, $countSuiviUsager);
    }

    public function countUserData(?Territory $territory = null): CountUser
    {
        return $this->userRepository->countUserByStatus($territory);
    }
}
