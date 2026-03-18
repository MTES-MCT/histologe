<?php

namespace App\Controller\Back;

use App\Entity\Signalement;
use App\Form\SearchHistoryEntryType;
use App\Manager\HistoryEntryManager;
use App\Repository\HistoryEntryRepository;
use App\Repository\SignalementRepository;
use App\Security\Voter\SignalementVoter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/history')]
class HistoryEntryController extends AbstractController
{
    #[Route('/signalement/{id}/affectations', name: 'history_affectation', methods: ['GET'])]
    public function listHistoryAffectation(
        string $id,
        HistoryEntryManager $historyEntryManager,
        SignalementRepository $signalementRepository,
    ): Response {
        $signalement = $signalementRepository->find($id);
        if (
            !$signalement
            || !$this->isGranted(SignalementVoter::SIGN_VIEW, $signalement)
            || !$this->isGranted(SignalementVoter::SIGN_AFFECTATION_SEE, $signalement)
        ) {
            return $this->json(['response' => 'error'], Response::HTTP_FORBIDDEN);
        }

        $historyEntries = $historyEntryManager->getAffectationHistory($signalement);

        return $this->json(['historyEntries' => $historyEntries]);
    }

    #[Route('/entry-diff', name: 'back_history_entry_diff', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function historyEntryDetails(
        HistoryEntryRepository $historyEntryRepository,
        EntityManagerInterface $entityManager,
        Request $request,
    ): Response {
        $entity_name = $request->query->get('entity_name', '');
        $entity_id = $request->query->get('entity_id', '');
        $orderType = $request->query->get('orderType', 'ASC');
        $entity = null;
        $entityUrl = null;
        $historyEntries = [];
        $relatedEntities = [];

        $searchForm = $this->createForm(SearchHistoryEntryType::class, ['entity_name' => $entity_name, 'entity_id' => $entity_id, 'orderType' => $orderType]);
        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $fullEntityName = 'App\\Entity\\'.$entity_name;
            /** @var class-string $fullEntityName */
            $historyEntries = $historyEntryRepository->findBy(['entityId' => $entity_id, 'entityName' => $fullEntityName], ['createdAt' => $orderType]);
            $entity = $entityManager->getRepository($fullEntityName)->find($entity_id);
            if ($entity instanceof Signalement) {
                $entityUrl = $this->generateUrl('back_signalement_view', ['uuid' => $entity->getUuid()]);
            }
            foreach ($entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
                foreach ($metadata->getAssociationMappings() as $fieldName => $mapping) {
                    if ($mapping['targetEntity'] === $fullEntityName && ClassMetadata::MANY_TO_ONE == $mapping['type'] && empty($mapping['mappedBy'])) {
                        $shortName = $metadata->getReflectionClass()->getShortName();
                        if ('HistoryEntry' === $shortName) {
                            continue;
                        }
                        $relatedEntities[$shortName] = $entityManager->getRepository($metadata->getName())->findBy([$fieldName => $entity]);
                    }
                }
            }
        }

        return $this->render('back/history-entry/details.html.twig', [
            'entityId' => $entity_id,
            'entityName' => $entity_name,
            'entity' => $entity,
            'entityUrl' => $entityUrl,
            'historyEntries' => $historyEntries,
            'relatedEntities' => $relatedEntities,
            'searchForm' => $searchForm,
        ]);
    }
}
