<?php

namespace App\Service\DashboardTabPanel;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\User;
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
        private readonly TabCacheCommonKeyGenerator $cacheCommonKeyGenerator,
        private readonly Security $security,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        $this->initKeyCache();
    }

    private function initKeyCache(): void
    {
        // TODO : à voir si on veut utiliser ça
        $this->commonKey = $this->cacheCommonKeyGenerator->generate().'-tab-';
    }

    /**
     * @return TabDossier[]
     */
    public function getDernierActionDossiers(?TabQueryParameters $tabQueryParameters = null): array
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';
        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey;

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_dossier_cache_expired_after'));

                return $this->tabDataManager->getDernierActionDossiers($tabQueryParameters);
            }
        );
    }

    public function countUsersPendingToArchive(?TabQueryParameters $tabQueryParameters = null): int
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';
        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey;

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_kpi_cache_expired_after'));

                return $this->tabDataManager->countUsersPendingToArchive($tabQueryParameters);
            }
        );
    }

    public function countPartenairesNonNotifiables(?TabQueryParameters $tabQueryParameters = null): int
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';
        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey;

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_kpi_cache_expired_after'));

                return $this->tabDataManager->countPartenairesNonNotifiables($tabQueryParameters);
            }
        );
    }

    public function countPartenairesInterfaces(?TabQueryParameters $tabQueryParameters = null): int
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';
        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey;

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_kpi_cache_expired_after'));

                return $this->tabDataManager->countPartenairesInterfaces($tabQueryParameters);
            }
        );
    }

    /**
     * @return array<string, bool|\DateTimeImmutable|null>
     *
     * @throws \DateMalformedStringException
     */
    public function getInterconnexions(?TabQueryParameters $tabQueryParameters = null): array
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';
        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey;

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_kpi_cache_expired_after'));

                return $this->tabDataManager->getInterconnexions($tabQueryParameters);
            }
        );
    }

    public function getNouveauxDossiersWithCount(?SignalementStatus $signalementStatus = null, ?AffectationStatus $affectationStatus = null, ?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';
        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey.'-'.($signalementStatus?->value ?? 'null').'-'.($affectationStatus?->value ?? 'null');

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $signalementStatus, $affectationStatus, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_dossier_cache_expired_after'));

                return $this->tabDataManager->getNouveauxDossiersWithCount($signalementStatus, $affectationStatus, $tabQueryParameters);
            }
        );
    }

    public function getDossierNonAffectationWithCount(SignalementStatus $signalementStatus, ?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';
        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey.'-'.($signalementStatus?->value ?? 'null');

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $signalementStatus, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_dossier_cache_expired_after'));

                return $this->tabDataManager->getDossierNonAffectationWithCount($signalementStatus, $tabQueryParameters);
            }
        );
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
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';

        $tabQueryParameters = new TabQueryParameters(
            territoireId: $territoryId,
            mesDossiersMessagesUsagers: $mesDossiersMessagesUsagers,
            mesDossiersAverifier: $mesDossiersAverifier,
            queryCommune: $queryCommune,
            partners: $partners
        );

        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey;

        // $paramsKey = implode('_', [
        //     implode('-', $territories),
        //     $territoryId ?? 'null',
        //     $mesDossiersMessagesUsagers ?? 'null',
        //     $mesDossiersAverifier ?? 'null',
        //     $queryCommune ?? 'null',
        //     is_array($partners) ? implode('-', $partners) : $partners,
        // ]);
        // $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey;

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $territories, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_kpi_cache_expired_after'));

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

    public function getMessagesUsagersNouveauxMessages(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';
        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey;

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_suivis_cache_expired_after'));

                return $this->tabDataManager->getMessagesUsagersNouveauxMessages($tabQueryParameters);
            }
        );
    }

    public function getMessagesUsagersMessageApresFermeture(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';
        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey;

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_suivis_cache_expired_after'));

                return $this->tabDataManager->getMessagesUsagersMessageApresFermeture($tabQueryParameters);
            }
        );
    }

    public function getMessagesUsagersMessagesSansReponse(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';
        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey;

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_suivis_cache_expired_after'));

                return $this->tabDataManager->getMessagesUsagersMessagesSansReponse($tabQueryParameters);
            }
        );
    }

    public function getDossiersAVerifierSansActivitePartenaires(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';
        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey;

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_dossier_cache_expired_after'));

                return $this->tabDataManager->getDossiersAVerifierSansActivitePartenaires($tabQueryParameters);
            }
        );
    }

    public function getDossiersDemandesFermetureByUsager(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';
        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey;

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_dossier_cache_expired_after'));

                return $this->tabDataManager->getDossiersDemandesFermetureByUsager($tabQueryParameters);
            }
        );
    }

    public function getDossiersRelanceSansReponse(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';
        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey;

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_dossier_cache_expired_after'));

                return $this->tabDataManager->getDossiersRelanceSansReponse($tabQueryParameters);
            }
        );
    }

    public function getDossiersFermePartenaireTous(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        $roleKey = $user ? implode('-', $user->getRoles()) : 'anonymous';
        $paramsKey = $this->getTabQueryParametersKey($user, $tabQueryParameters);
        $key = __FUNCTION__.'-'.$this->commonKey.$roleKey.'-'.$paramsKey;

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($user, $tabQueryParameters) {
                $this->addTagTerritory($item, $user, $tabQueryParameters);
                $item->expiresAfter($this->parameterBag->get('tab_data_dossier_cache_expired_after'));

                return $this->tabDataManager->getDossiersFermePartenaireTous($tabQueryParameters);
            }
        );
    }

    private function addTagTerritory(ItemInterface $item, ?User $user, ?TabQueryParameters $tabQueryParameters): void
    {
        $tags = [];
        if (1 === count($user->getPartnersTerritories()) || ($tabQueryParameters && $tabQueryParameters->territoireId)) {
            $tags[] = 'territory-'.($tabQueryParameters->territoireId ?? $user->getFirstTerritory()->getId());
        } else {
            foreach ($user->getPartnersTerritories() as $territory) {
                $tags[] = 'territory-'.$territory->getId();
            }
        }
        if ($tags) {
            $item->tag($tags);
        }
    }

    private function getTabQueryParametersKey(User $user, ?TabQueryParameters $params): string
    {
        // TODO : ajouter la liste des territoires de l'utilisateur ici ? (de l'utilisateur connecté)
        if (!$params) {
            return 'noparams';
        }
        $data = [
            $params->territoireId,
            $params->communeCodePostal,
            $params->createdFrom,
            is_array($params->partenairesId) ? implode('-', $params->partenairesId) : $params->partenairesId,
            is_array($params->partners) ? implode('-', $params->partners) : $params->partners,
            $params->sortBy,
            $params->orderBy,
            $params->mesDossiersMessagesUsagers,
            $params->mesDossiersAverifier,
            $params->queryCommune,
        ];

        return implode('_', array_map(fn ($v) => $v ?? 'null', $data));
    }
}
