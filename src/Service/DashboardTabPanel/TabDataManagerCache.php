<?php

namespace App\Service\DashboardTabPanel;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\User;
use App\Repository\TerritoryRepository;
use App\Service\DashboardTabPanel\Kpi\TabCountKpi;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class TabDataManagerCache implements TabDataManagerInterface
{
    private ?string $commonKey = null;

    public function __construct(
        private readonly TabDataManager $tabDataManager,
        private readonly TagAwareCacheInterface $dashboardCache,
        private readonly Security $security,
        private readonly ParameterBagInterface $parameterBag,
        private readonly TerritoryRepository $territoryRepository,
    ) {
        $this->initKeyCache();
    }

    private function initKeyCache(): void
    {
        $this->commonKey = 'dashboard-tab';
    }

    /**
     * @param array<int, mixed> $territories
     * @param array<int, int>   $partners
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countDataKpi(array $territories, ?int $territoryId, ?string $mesDossiersMessagesUsagers, ?string $mesDossiersAverifier, ?string $queryCommune, ?array $partners): TabCountKpi
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        $tabQueryParameters = new TabQueryParameters(
            territoireId: $territoryId,
            mesDossiersMessagesUsagers: $mesDossiersMessagesUsagers,
            mesDossiersAverifier: $mesDossiersAverifier,
            queryCommune: $queryCommune,
            partners: $partners
        );

        return $this->tabDataManager->countDataKpi(
            $territories,
            $tabQueryParameters->territoireId,
            $tabQueryParameters->mesDossiersMessagesUsagers,
            $tabQueryParameters->mesDossiersAverifier,
            $tabQueryParameters->queryCommune,
            $tabQueryParameters->partners
        );

        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = $this->commonKey.'-'.__FUNCTION__.'-'.$paramsKey;

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($territories, $tabQueryParameters) {
                // $item->expiresAfter($this->parameterBag->get('tab_data_kpi_cache_expired_after'));
                $item->expiresAfter(1);

                return $this->tabDataManager->countDataKpi(
                    $territories,
                    $tabQueryParameters->territoireId,
                    $tabQueryParameters->mesDossiersMessagesUsagers,
                    $tabQueryParameters->mesDossiersAverifier,
                    $tabQueryParameters->queryCommune,
                    $tabQueryParameters->partners
                );
            }
        );
    }

    /**
     * @return TabDossier[]
     */
    public function getDernierActionDossiers(?TabQueryParameters $tabQueryParameters = null): array
    {
        return $this->tabDataManager->getDernierActionDossiers($tabQueryParameters);
    }

    public function countUsersPendingToArchive(?TabQueryParameters $tabQueryParameters = null): int
    {
        return $this->tabDataManager->countUsersPendingToArchive($tabQueryParameters);
    }

    public function countPartenairesNonNotifiables(?TabQueryParameters $tabQueryParameters = null): int
    {
        return $this->tabDataManager->countPartenairesNonNotifiables($tabQueryParameters);
    }

    public function countPartenairesInterfaces(?TabQueryParameters $tabQueryParameters = null): int
    {
        return $this->tabDataManager->countPartenairesInterfaces($tabQueryParameters);
    }

    /**
     * @return array<string, bool|\DateTimeImmutable|null>
     *
     * @throws \DateMalformedStringException
     */
    public function getInterconnexions(?TabQueryParameters $tabQueryParameters = null): array
    {
        return $this->tabDataManager->getInterconnexions($tabQueryParameters);
    }

    public function getNouveauxDossiersWithCount(?SignalementStatus $signalementStatus = null, ?AffectationStatus $affectationStatus = null, ?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        return $this->tabDataManager->getNouveauxDossiersWithCount($signalementStatus, $affectationStatus, $tabQueryParameters);
    }

    public function getDossierNonAffectationWithCount(SignalementStatus $signalementStatus, ?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        return $this->tabDataManager->getDossierNonAffectationWithCount($signalementStatus, $tabQueryParameters);
    }

    public function getDossiersFermePartenaireTous(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        return $this->tabDataManager->getDossiersFermePartenaireTous($tabQueryParameters);
    }

    public function getDossiersDemandesFermetureByUsager(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        return $this->tabDataManager->getDossiersDemandesFermetureByUsager($tabQueryParameters);
    }

    public function getDossiersRelanceSansReponse(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        return $this->tabDataManager->getDossiersRelanceSansReponse($tabQueryParameters);
    }

    public function getMessagesUsagersNouveauxMessages(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        return $this->tabDataManager->getMessagesUsagersNouveauxMessages($tabQueryParameters);
    }

    public function getMessagesUsagersMessageApresFermeture(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        return $this->tabDataManager->getMessagesUsagersMessageApresFermeture($tabQueryParameters);
    }

    public function getMessagesUsagersMessagesSansReponse(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        return $this->tabDataManager->getMessagesUsagersMessagesSansReponse($tabQueryParameters);
    }

    public function getDossiersAVerifierSansActivitePartenaires(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        return $this->tabDataManager->getDossiersAVerifierSansActivitePartenaires($tabQueryParameters);
    }

    private function getTerritoryKey(User $user, ?TabQueryParameters $tabQueryParameters): string
    {
        $territories = [];
        if (1 === count($user->getPartnersTerritories()) || ($tabQueryParameters && $tabQueryParameters->territoireId)) {
            $territories[] = 'territory_'.($tabQueryParameters->territoireId ?? $user->getFirstTerritory()->getId());
        } elseif ($this->security->isGranted('ROLE_ADMIN')) {
            $territories[] = 'territory_all';
        } else {
            foreach ($user->getPartnersTerritories() as $territory) {
                $territories[] = 'territory_'.$territory->getId();
            }
        }
        asort($territories);

        return implode('-', $territories);
    }

    private function getPartnerKey(User $user, ?TabQueryParameters $tabQueryParameters): string
    {
        $partners = [];
        if ($tabQueryParameters && $tabQueryParameters->partners) {
            foreach ($tabQueryParameters->partners as $partner) {
                $partners[] = 'partner_'.$partner;
            }
        } elseif ($tabQueryParameters && $tabQueryParameters->partenairesId) {
            foreach ($tabQueryParameters->partenairesId as $partnerId) {
                $partners[] = 'partner_'.$partnerId;
            }
        } elseif ($this->security->isGranted('ROLE_ADMIN')) {
            $partners[] = 'partner_all';
        } elseif ($this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            $partners[] = 'partner_'.$user->getFirstTerritory().'-all';
        } else {
            foreach ($user->getPartners() as $partner) {
                $partners[] = 'partner_'.$partner->getId();
            }
        }
        asort($partners);

        return implode('-', $partners);
    }

    private function getTabQueryParametersKey(User $user, ?TabQueryParameters $params): string
    {
        $roleKey = implode('-', array_filter($user->getRoles(), fn ($role) => 'ROLE_USER' !== $role));
        $territoryKey = $this->getTerritoryKey($user, $params);
        $partnerKey = $this->getPartnerKey($user, $params);
        if ($params) {
            $otherParams = [
                'commune' => $params->communeCodePostal,
                'mesDossiersMessagesUsagers' => $params->mesDossiersMessagesUsagers,
                'mesDossiersAverifier' => $params->mesDossiersAverifier,
            ];
            $otherParamsKey = implode(
                '-',
                array_map(
                    fn ($k, $v) => null !== $v && '' !== $v ? $k.'-'.$v : null,
                    array_keys($otherParams),
                    array_values($otherParams)
                )
            );
            $otherParamsKey = trim(preg_replace('/-+/', '-', $otherParamsKey), '-');
        }

        return $roleKey.'-'.$territoryKey.'-'.$partnerKey.'-'.$otherParamsKey;
    }
}
