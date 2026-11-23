<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\SignalementSearchQuery;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Repository\Query\Partner\PartnerLocalizationQuery;
use App\Repository\Query\SignalementList\QueryBuilderFactory;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\Signalement\SearchFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/signalements/affectation-en-masse')]
#[IsGranted('ROLE_ADMIN')]
class MassAffectationController extends AbstractController
{
    #[Route('/analyse', name: 'back_signalements_mass_affectation_analyze', methods: ['GET'])]
    public function analyze(
        QueryBuilderFactory $queryBuilderFactory,
        SearchFilter $searchFilter,
        PartnerRepository $partnerRepository,
        SignalementRepository $signalementRepository,
        TerritoryRepository $territoryRepository,
        PartnerLocalizationQuery $partnerLocalizationQuery,
        #[MapQueryParameter] int $partnerId,
        #[MapQueryString] ?SignalementSearchQuery $signalementSearchQuery = null,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $filters = $searchFilter->setRequest($signalementSearchQuery)->buildFilters($user);
        $ids = $queryBuilderFactory->create($user, $filters)->getQuery()->getSingleColumnResult();

        /** @var Partner $partner */
        $partner = $partnerRepository->find($partnerId);
        if (!$partner) {
            return new JsonResponse(['message' => 'Partenaire introuvable'], Response::HTTP_NOT_FOUND);
        }
        /** @var Territory $territory */
        $territory = $territoryRepository->find($signalementSearchQuery->getTerritoire());
        if (!$territory) {
            return new JsonResponse(['message' => 'Territoire introuvable'], Response::HTTP_NOT_FOUND);
        }
        if ($partner->getTerritory() !== $territory) {
            return new JsonResponse(['message' => 'Le partenaire n\'est pas dans le périmètre du territoire sélectionné'], Response::HTTP_BAD_REQUEST);
        }

        $signalementIdsInPerimeter = $partnerLocalizationQuery->findSignalementIdsInPartnerPerimeter($partner, $ids);
        /** @var Signalement[] $signalements */
        $signalements = $signalementRepository->findBy(['id' => $ids]);

        $signalementsToAffect = [];
        $signalementsAlreadyAffected = [];
        $signalementsOutOfPerimeter = [];
        foreach ($signalements as $signalement) {
            if (!in_array($signalement->getId(), $signalementIdsInPerimeter)) {
                $signalementsOutOfPerimeter[] = $signalement->getId();
                continue;
            }
            if ($signalement->getAffectationForPartner($partner)) {
                $signalementsAlreadyAffected[] = $signalement->getId();
                continue;
            }
            $signalementsToAffect[] = $signalement->getId();
        }
        $partnerInterconnection = false;
        if ($partner->canSyncWithEsabora() || $partner->canSyncWithIdoss()) {
            $partnerInterconnection = true;
        }

        return new JsonResponse([
            'signalementsToAffect' => $signalementsToAffect,
            'signalementsAlreadyAffected' => $signalementsAlreadyAffected,
            'signalementsOutOfPerimeter' => $signalementsOutOfPerimeter,
            'partnerInterconnection' => $partnerInterconnection,
        ]);
    }

    #[Route('/valider', name: 'back_signalements_mass_affectation_validate', methods: ['POST'])]
    // body JSON = partnerId + ids des signalements, avec un token CSRF
    public function validate(
        // QueryBuilderFactory $queryBuilderFactory,
        // SearchFilter $searchFilter,
        // #[MapQueryString] ?SignalementSearchQuery $signalementSearchQuery = null,
    ): Response {
        // TODO : récupérer les ids des signalements et l'id du partenaire à affecter depuis le body JSON
        /* @var User $user */
        // $user = $this->getUser();
        // TODO : dispatcher un message

        return new JsonResponse([]);
    }
}
