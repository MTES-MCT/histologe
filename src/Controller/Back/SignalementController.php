<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\SignalementClosedEvent;
use App\Event\SignalementViewedEvent;
use App\Form\ClotureType;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Repository\AffectationRepository;
use App\Repository\CriticiteRepository;
use App\Repository\DesordreCategorieRepository;
use App\Repository\DesordreCritereRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Repository\InterventionRepository;
use App\Repository\NotificationRepository;
use App\Repository\SignalementQualificationRepository;
use App\Repository\TagRepository;
use App\Repository\ZoneRepository;
use App\Security\Voter\AssignmentVoter;
use App\Security\Voter\SignalementVoter;
use App\Service\Signalement\PhotoHelper;
use App\Service\Signalement\SignalementDesordresProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/signalements')]
class SignalementController extends AbstractController
{
    #[Route('/{uuid:signalement}', name: 'back_signalement_view')]
    public function viewSignalement(
        Signalement $signalement,
        Request $request,
        TagRepository $tagsRepository,
        SignalementManager $signalementManager,
        AffectationManager $affectationManager,
        EventDispatcherInterface $eventDispatcher,
        ParameterBagInterface $parameterBag,
        SignalementQualificationRepository $signalementQualificationRepository,
        CriticiteRepository $criticiteRepository,
        AffectationRepository $affectationRepository,
        InterventionRepository $interventionRepository,
        DesordrePrecisionRepository $desordrePrecisionsRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        DesordreCategorieRepository $desordreCategorieRepository,
        DesordreCritereRepository $desordreCritereRepository,
        ZoneRepository $zoneRepository,
    ): Response {
        // load desordres data to prevent n+1 queries
        $desordreCategorieRepository->findAll();
        $desordreCritereRepository->findAll();
        /** @var User $user */
        $user = $this->getUser();
        if (Signalement::STATUS_ARCHIVED === $signalement->getStatut()) {
            $this->addFlash('error', "Ce signalement a été archivé et n'est pas consultable.");

            return $this->redirectToRoute('back_signalement_index');
        }
        $this->denyAccessUnlessGranted('SIGN_VIEW', $signalement);

        $eventDispatcher->dispatch(
            new SignalementViewedEvent($signalement, $user),
            SignalementViewedEvent::NAME
        );

        $canAnswerAssignment = $canCancelRefusedAssignment = false;
        $isAssignmentRefused = $isAssignmentAccepted = false;
        $isClosedForMe = null;
        $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
        if ($affectation = $signalement->getAffectations()->filter(function (Affectation $affectation) use ($partner) {
            return $affectation->getPartner() === $partner;
        })->first()) {
            switch ($affectation->getStatut()) {
                case Affectation::STATUS_ACCEPTED:
                    $isAssignmentAccepted = true;
                    break;
                case Affectation::STATUS_REFUSED:
                    $isAssignmentRefused = true;
                    $canCancelRefusedAssignment = $this->isGranted(AssignmentVoter::ANSWER, $affectation);
                    break;
                case Affectation::STATUS_CLOSED:
                    $isClosedForMe = true;
                    break;
                case Affectation::STATUS_WAIT:
                    $canAnswerAssignment = $this->isGranted(AssignmentVoter::ANSWER, $affectation);
                    break;
            }
        }

        $isClosedForMe = $isClosedForMe ?? Signalement::STATUS_CLOSED === $signalement->getStatut();
        $canValidateOrRefuseSignalement = $this->isGranted(SignalementVoter::VALIDATE, $signalement)
                                            && !$isClosedForMe
                                            && Signalement::STATUS_NEED_VALIDATION === $signalement->getStatut();
        $canReopenAssignment = $affectation ? $this->isGranted(AssignmentVoter::REOPEN, $affectation) : false;

        $clotureForm = $this->createForm(ClotureType::class);
        $clotureForm->handleRequest($request);
        $eventParams = [];
        if ($clotureForm->isSubmitted() && $clotureForm->isValid()) {
            $eventParams['motif_cloture'] = $clotureForm->get('motif')->getData();
            $eventParams['motif_suivi'] = $clotureForm->getExtraData()['suivi'];
            if (mb_strlen($eventParams['motif_suivi']) < 10) {
                $this->addFlash('error', 'Le motif de suivi doit contenir au moins 10 caractères.');

                return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
            }
            $eventParams['suivi_public'] = false;
            if ($this->isGranted('ROLE_ADMIN_TERRITORY') && isset($clotureForm->getExtraData()['publicSuivi'])) {
                $eventParams['suivi_public'] = $clotureForm->getExtraData()['publicSuivi'];
            }
            $eventParams['subject'] = $partner->getNom();
            $eventParams['closed_for'] = $clotureForm->get('type')->getData();

            $entity = $reference = null;
            if ('all' === $eventParams['closed_for'] && $this->isGranted('ROLE_ADMIN_TERRITORY')) {
                $eventParams['subject'] = 'tous les partenaires';
                $entity = $signalement = $signalementManager->closeSignalementForAllPartners(
                    $signalement,
                    $eventParams['motif_cloture']
                );
                $reference = $signalement->getReference();

            /* @var Affectation $affectation */
            } elseif ($affectation) {
                $entity = $affectationManager->closeAffectation($affectation, $user, $eventParams['motif_cloture'], true);
                $reference = $entity->getSignalement()->getReference();
            }

            if (!empty($entity)) {
                $eventDispatcher->dispatch(new SignalementClosedEvent($entity, $eventParams), SignalementClosedEvent::NAME);
                $this->addFlash('success', sprintf('Signalement #%s cloturé avec succès !', $reference));
            }
            $signalementSearchQuery = $request->getSession()->get('signalementSearchQuery');
            $url = $signalementSearchQuery ? $this->generateUrl('back_signalement_index').$signalementSearchQuery->getQueryStringForUrl() : $this->generateUrl('back_signalement_index');

            return $this->redirect($url);
        }
        $infoDesordres = $signalementDesordresProcessor->process($signalement);

        $canEditSignalement = false;
        if (Signalement::STATUS_ACTIVE === $signalement->getStatut()) {
            $canEditSignalement = $this->isGranted('ROLE_ADMIN')
                || $this->isGranted('ROLE_ADMIN_TERRITORY')
                || $isAssignmentAccepted;
        }

        $signalementQualificationNDE = $signalementQualificationRepository->findOneBy([
            'signalement' => $signalement,
            'qualification' => Qualification::NON_DECENCE_ENERGETIQUE, ]);

        if (null == $signalement->getCreatedFrom()) {
            $signalementQualificationNDECriticites = $signalementQualificationNDE
                ? $criticiteRepository->findBy(['id' => $signalementQualificationNDE->getCriticites()])
                : null;
        } else {
            $signalementQualificationNDECriticites = $signalementQualificationNDE
                ? $desordrePrecisionsRepository->findBy(
                    ['id' => $signalementQualificationNDE->getDesordrePrecisionIds()]
                )
                : null;
        }

        $partners = $signalementManager->findAllPartners($signalement);

        $files = $parameterBag->get('files');

        $canEditNDE = $this->isGranted('SIGN_EDIT_NDE', $signalement);
        $listQualificationStatusesLabelsCheck = [];
        if (null !== $signalement->getSignalementQualifications()) {
            foreach ($signalement->getSignalementQualifications() as $qualification) {
                if (!$qualification->isPostVisite()) {
                    $listQualificationStatusesLabelsCheck[] = $qualification->getStatus()->label();
                }
            }
        }

        $listConcludeProcedures = [];
        if (null !== $signalement->getInterventions()) {
            foreach ($signalement->getInterventions() as $intervention) {
                if (Intervention::STATUS_DONE == $intervention->getStatus()) {
                    $listConcludeProcedures = array_merge(
                        $listConcludeProcedures,
                        $intervention->getConcludeProcedure()
                    );
                }
            }
        }
        $listConcludeProcedures = array_unique(array_map(function ($concludeProcedure) {
            return $concludeProcedure->label();
        }, $listConcludeProcedures));

        $partnerVisite = $affectationRepository->findAffectationWithQualification(Qualification::VISITES, $signalement);

        $allPhotosOrdered = PhotoHelper::getSortedPhotos($signalement);
        $twigParams = [
            'title' => '#'.$signalement->getReference().' Signalement',
            'createdFromDraft' => $signalement->getCreatedFrom(),
            'situations' => $infoDesordres['criticitesArranged'],
            'photos' => $infoDesordres['photos'],
            'criteres' => $infoDesordres['criteres'],
            'canEditSignalement' => $canEditSignalement,
            'canAnswerAssignment' => $canAnswerAssignment,
            'canCancelRefusedAssignment' => $canCancelRefusedAssignment,
            'canValidateOrRefuseSignalement' => $canValidateOrRefuseSignalement,
            'canReopenAssignment' => $canReopenAssignment,
            'affectation' => $affectation,
            'isAssignmentAccepted' => $isAssignmentAccepted,
            'isSignalementClosed' => Signalement::STATUS_CLOSED === $signalement->getStatut(),
            'isClosedForMe' => $isClosedForMe,
            'isAssignmentRefused' => $isAssignmentRefused,
            'isDanger' => $infoDesordres['isDanger'],
            'signalement' => $signalement,
            'partners' => $partners,
            'clotureForm' => $clotureForm->createView(),
            'tags' => $tagsRepository->findAllActive($signalement->getTerritory()),
            'signalementQualificationNDE' => $signalementQualificationNDE,
            'signalementQualificationNDECriticite' => $signalementQualificationNDECriticites,
            'files' => $files,
            'canEditNDE' => $canEditNDE,
            'listQualificationStatusesLabelsCheck' => $listQualificationStatusesLabelsCheck,
            'listConcludeProcedures' => $listConcludeProcedures,
            'partnersCanVisite' => $partnerVisite,
            'pendingVisites' => $interventionRepository->getPendingVisitesForSignalement($signalement),
            'allPhotosOrdered' => $allPhotosOrdered,
            'canTogglePartnerAffectation' => $this->isGranted('ASSIGN_TOGGLE', $signalement),
            'canSeePartnerAffectation' => $this->isGranted('ASSIGN_SEE', $signalement),
            'zones' => $zoneRepository->findZonesBySignalement($signalement),
        ];

        return $this->render('back/signalement/view.html.twig', $twigParams);
    }

    #[Route('/{uuid:signalement}/supprimer', name: 'back_signalement_delete', methods: 'POST')]
    public function newDeleteSignalement(
        Signalement $signalement,
        Request $request,
        ManagerRegistry $doctrine,
        AffectationRepository $affectationRepository,
        NotificationRepository $notificationRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('SIGN_DELETE', $signalement);
        if ($this->isCsrfTokenValid(
            'signalement_delete_'.$signalement->getId(),
            $request->getPayload()->get('_token')
        )
        ) {
            $signalement->setStatut(Signalement::STATUS_ARCHIVED);
            $notificationRepository->deleteBySignalement($signalement);
            $affectationRepository->deleteByStatusAndSignalement(Affectation::STATUS_WAIT, $signalement);
            $doctrine->getManager()->flush();
            $response = [
                'status' => Response::HTTP_OK,
                'message' => \sprintf('Le signalement %s a bien été supprimé.', $signalement->getReference()),
            ];
        } else {
            $response = [
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Une erreur s\'est produite lors de la suppression. Veuillez réessayer plus tard.',
            ];
        }

        return $this->json($response, $response['status']);
    }

    #[Route('/{uuid:signalement}/save-tags', name: 'back_signalement_save_tags', methods: 'POST')]
    public function saveSignalementTags(
        Signalement $signalement,
        Request $request,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);

        if (
            $this->isCsrfTokenValid('signalement_save_tags', $request->request->get('_token'))
        ) {
            $tagIds = $request->request->get('tag-ids');
            $tagList = explode(',', $tagIds);
            foreach ($signalement->getTags() as $existingTag) {
                if (!\in_array($existingTag->getId(), $tagList)) {
                    $signalement->removeTag($existingTag);
                }
            }
            if (!empty($tagIds)) {
                foreach ($tagList as $tagId) {
                    $tag = $tagRepository->findBy([
                        'id' => $tagId,
                        'territory' => $signalement->getTerritory(),
                        'isArchive' => 0,
                    ]);
                    if (!empty($tag)) {
                        $signalement->addTag($tag[0]);
                    }
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Les étiquettes ont bien été enregistrées.');
        } else {
            $this->addFlash('error', 'Erreur lors de la modification des étiquettes !');
        }

        return $this->redirect($this->generateUrl('back_signalement_view', [
            'uuid' => $signalement->getUuid(),
        ]));
    }
}
