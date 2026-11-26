<?php

namespace App\Controller\Back;

use App\Dto\Request\MassAffectationValidateRequest;
use App\Dto\Request\Signalement\SignalementSearchQuery;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Messenger\Message\MassAffectationMessage;
use App\Repository\PartnerRepository;
use App\Repository\Query\Partner\PartnerLocalizationQuery;
use App\Repository\Query\Signalement\SignalementMassAffectationQuery;
use App\Repository\Query\SignalementList\QueryBuilderFactory;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\MessageHelper;
use App\Service\Signalement\SearchFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
            $infoSignalement = [
                'id' => $signalement->getId(),
                'reference' => $signalement->getReference(),
                'identite_occupant' => $signalement->getNomOccupantComplet(),
                'adresse' => $signalement->getAddress()->getFull(),
            ];
            if (!in_array($signalement->getId(), $signalementIdsInPerimeter)) {
                $signalementsOutOfPerimeter[] = $infoSignalement;
                continue;
            }
            if ($signalement->getAffectationForPartner($partner)) {
                $signalementsAlreadyAffected[] = $infoSignalement;
                continue;
            }
            $signalementsToAffect[] = $infoSignalement;
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
    public function validate(
        #[MapRequestPayload(validationGroups: ['false'])]
        MassAffectationValidateRequest $massAffectationValidateRequest,
        ValidatorInterface $validator,
        PartnerRepository $partnerRepository,
        MessageBusInterface $messageBus,
        SignalementMassAffectationQuery $signalementMassAffectationQuery,
    ): Response {
        if (!$this->isCsrfTokenValid('mass_affectation_validate', $massAffectationValidateRequest->_token)) {
            return new JsonResponse(['message' => MessageHelper::ERROR_MESSAGE_CSRF], Response::HTTP_BAD_REQUEST);
        }
        $errors = $validator->validate($massAffectationValidateRequest);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }

            return new JsonResponse(['message' => implode("\n", $messages)], Response::HTTP_BAD_REQUEST);
        }

        $partnerId = $massAffectationValidateRequest->getPartnerId();

        /** @var Partner $partner */
        $partner = $partnerRepository->find($partnerId);
        if (!$partner) {
            return new JsonResponse(['message' => 'Partenaire introuvable'], Response::HTTP_NOT_FOUND);
        }
        if ($partner->getIsArchive()) {
            return new JsonResponse(['message' => 'Partenaire archivé'], Response::HTTP_NOT_FOUND);
        }
        if (null === $partner->getTerritory()) {
            return new JsonResponse(['message' => 'Le partenaire n\'est pas rattaché à un territoire'], Response::HTTP_NOT_FOUND);
        }

        $signalementsIds = array_values(array_unique($massAffectationValidateRequest->getIds()));
        $eligibleCount = $signalementMassAffectationQuery->countActiveInTerritory($signalementsIds, $partner->getTerritory());
        if ($eligibleCount !== count($signalementsIds)) {
            return new JsonResponse(
                ['message' => 'Certains signalements ne sont plus éligibles à l\'affectation. Veuillez relancer l\'analyse.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        /** @var User $user */
        $user = $this->getUser();
        $messageBus->dispatch(new MassAffectationMessage(
            partnerId: $partner->getId(),
            signalementIds: $signalementsIds,
            userId: $user->getId(),
        ));

        return new JsonResponse([]);
    }
}
