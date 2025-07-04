<?php

namespace App\Service\Signalement\Export;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Repository\PartnerRepository;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\SecurityBundle\Security;

class SignalementExportFiltersDisplay
{
    /** @var array<string> FILTERS_LABELS */
    private const array FILTERS_LABELS = [
        'isImported' => 'Signalement importés',
        'territories' => 'Territoires',
        'partners' => 'Partenaires',
        'searchterms' => 'Recherche',
        'cities' => 'Ville ou code postal',
        'statuses' => 'Statut',
        'epcis' => 'EPCI',
        'procedure' => 'Procédure suspectée',
        'procedureConstatee' => 'Procédure constatée',
        'dates' => 'Date de dépôt',
        'visites' => 'Visite',
        'typeDernierSuivi' => 'Type dernier suivi',
        'datesDernierSuivi' => 'Date dernier suivi',
        'statusAffectation' => 'Statut Affectation',
        'closed_affectation' => 'Affectation fermée',
        'enfantsM6' => 'Enfants de moins de 6 ans',
        'scores' => 'Criticité',
        'typeDeclarant' => 'Type de déclarant',
        'situation' => 'Situation',
        'bailleurSocial' => 'Bailleur',
        'tags' => 'Etiquettes',
        'housetypes' => 'Nature du parc',
        'allocs' => 'Allocataire',
        'delays' => 'Nb jours sans suivi',
        'nouveau_suivi' => 'Avec nouveau suivi',
        'motifCloture' => 'Motif de clôture',
    ];

    /** @var array<string> STATUS_AFFECTATION */
    private const array STATUS_AFFECTATION = [
        'accepte' => 'Acceptée',
        'en_attente' => 'En attente',
        'refuse' => 'Refusée',
        'cloture_un_partenaire' => 'Clôturée par au moins un partenaire',
        'cloture_tous_partenaire' => 'Clôturée par tous les partenaires',
    ];

    /** @var array<string> SITUATION_LIST */
    private const array SITUATION_LIST = [
        'attente_relogement' => 'Attente de relogement',
        'bail_en_cours' => 'Bail en cours',
        'preavis_de_depart' => 'Préavis de départ',
    ];

    /** @var array<string> CLOSED_AFFECTATION_LIST */
    private const array CLOSED_AFFECTATION_LIST = [
        'ONE_CLOSED' => 'Fermé chez au moins un partenaire',
    ];

    /** @var array<string> HOUSE_TYPES_LIST */
    private const array HOUSE_TYPES_LIST = [
        '1' => 'public',
        '0' => 'privee',
        'non_renseigne' => 'non renseigné',
    ];

    /** @var array<string> CHILDREN_LIST */
    private const array CHILDREN_LIST = [
        '1' => 'oui',
        '0' => 'non',
        'non_renseigne' => 'non renseigné',
    ];

    /** @var array<string> ALLOCS_LIST */
    private const array ALLOCS_LIST = [
        '1, caf, msa' => 'oui',
        '0' => 'non',
    ];

    public function __construct(
        private readonly TerritoryRepository $territoryRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly TagRepository $tagRepository,
        private readonly Security $security,
    ) {
    }

    /**
     * @param array<mixed> $filters
     *
     * @return array<string>
     */
    public function filtersToText(array $filters): array
    {
        unset($filters['page']);
        unset($filters['maxItemsPerPage']);
        unset($filters['sortBy']);
        unset($filters['orderBy']);
        unset($filters['signalement_ids']);
        unset($filters['delays_partners']);
        unset($filters['delays_territory']);

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            unset($filters['territories']);

            if (!$this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
                unset($filters['isImported']);
            }
        }

        $result = [];
        foreach ($filters as $filterName => $filterValue) {
            if (is_array($filterValue)) {
                $filterValue = implode(', ', $filterValue);
            } elseif (is_a($filterValue, 'App\Entity\Bailleur')) {
                $filterValue = $filterValue->getName();
            } else {
                if ('1' == $filterValue) {
                    $filterValue = 'Oui';
                }
                if ('0' == $filterValue) {
                    $filterValue = 'Non';
                }
            }

            if ('statuses' === $filterName) {
                $signalementStatus = SignalementStatus::tryFrom($filterValue);
                $filterValue = $signalementStatus->label();
            } elseif ('procedure' === $filterName) {
                $qualification = Qualification::tryFrom($filterValue);
                $filterValue = $qualification->label();
            } elseif ('statusAffectation' === $filterName) {
                $filterValue = self::STATUS_AFFECTATION[$filterValue] ?? $filterValue;
            } elseif ('closed_affectation' === $filterName) {
                $filterValue = self::CLOSED_AFFECTATION_LIST[$filterValue] ?? $filterValue;
            } elseif ('situation' === $filterName) {
                $filterValue = self::SITUATION_LIST[$filterValue] ?? $filterValue;
            } elseif ('housetypes' === $filterName) {
                $filterValue = self::HOUSE_TYPES_LIST[$filterValue] ?? $filterValue;
            } elseif ('enfantsM6' === $filterName) {
                $filterValue = self::CHILDREN_LIST[$filterValue] ?? $filterValue;
            } elseif ('allocs' === $filterName) {
                $filterValue = self::ALLOCS_LIST[$filterValue] ?? $filterValue;
            } elseif ('scores' === $filterName) {
                $scores = explode(', ', $filterValue);
                $filterValue = 'Entre '.implode(' et ', $scores);
            } elseif ('dates' === $filterName || 'datesDernierSuivi' === $filterName) {
                $filterValue = $this->getDatesFilterValue($filterValue);
            } elseif ('territories' === $filterName) {
                $territory = $this->territoryRepository->find($filterValue);
                $filterValue = $territory->getName();
            } elseif ('partners' === $filterName) {
                $filterValue = $this->getPartnersFilterValue($filterValue);
            } elseif ('tags' === $filterName) {
                $filterValue = $this->getTagFilterValue($filterValue);
            }

            if (isset(self::FILTERS_LABELS[$filterName])) {
                $filterName = self::FILTERS_LABELS[$filterName];
            }

            if (!empty($filterValue)) {
                $result[$filterName] = $filterValue;
            }
        }

        return $result;
    }

    private function getDatesFilterValue(string $filterValue): string
    {
        $listDates = explode(', ', $filterValue);
        $filterValue = 'Entre ';
        $startDate = new \DateTime($listDates[0]);
        $endDate = new \DateTime($listDates[1]);
        $filterValue .= $startDate->format('d/m/Y').' et '.$endDate->format('d/m/Y');

        return $filterValue;
    }

    private function getPartnersFilterValue(string $filterValue): string
    {
        if ('AUCUN' === $filterValue) {
            return 'AUCUN';
        }
        $listPartners = explode(', ', $filterValue);
        $filterValue = '';
        foreach ($listPartners as $idPartner) {
            $partner = $this->partnerRepository->find($idPartner);
            if (!$partner) {
                continue;
            }
            if (!empty($filterValue)) {
                $filterValue .= ', ';
            }
            $filterValue .= $partner->getNom();
        }

        return $filterValue;
    }

    private function getTagFilterValue(string $filterValue): string
    {
        $listTags = explode(', ', $filterValue);
        $filterValue = '';
        foreach ($listTags as $idTag) {
            $tag = $this->tagRepository->find($idTag);
            if (!empty($filterValue)) {
                $filterValue .= ', ';
            }
            $filterValue .= $tag->getLabel();
        }

        return $filterValue;
    }
}
