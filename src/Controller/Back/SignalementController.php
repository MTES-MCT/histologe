<?php

namespace App\Controller\Back;

use App\Dto\SignalementAffectationClose;
use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Event\SignalementClosedEvent;
use App\Event\SignalementViewedEvent;
use App\Form\AddSuiviType;
use App\Form\ClotureType;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Repository\AffectationRepository;
use App\Repository\CritereRepository;
use App\Repository\CriticiteRepository;
use App\Repository\DesordreCategorieRepository;
use App\Repository\DesordreCritereRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Repository\InterventionRepository;
use App\Repository\NotificationRepository;
use App\Repository\SignalementQualificationRepository;
use App\Repository\SignalementRepository;
use App\Repository\SituationRepository;
use App\Repository\TagRepository;
use App\Repository\ZoneRepository;
use App\Security\Voter\AffectationVoter;
use App\Security\Voter\SignalementVoter;
use App\Service\FormHelper;
use App\Service\Signalement\PhotoHelper;
use App\Service\Signalement\SignalementDesordresProcessor;
use App\Service\Signalement\SuiviSeenMarker;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/signalements')]
class SignalementController extends AbstractController
{
    /**
     * @throws \DateMalformedStringException
     * @throws Exception
     */
    #[Route('/{uuid:signalement}', name: 'back_signalement_view')]
    public function viewSignalement(
        Signalement $signalement,
        TagRepository $tagsRepository,
        SignalementManager $signalementManager,
        EventDispatcherInterface $eventDispatcher,
        SignalementQualificationRepository $signalementQualificationRepository,
        CriticiteRepository $criticiteRepository,
        AffectationRepository $affectationRepository,
        InterventionRepository $interventionRepository,
        DesordrePrecisionRepository $desordrePrecisionsRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        DesordreCategorieRepository $desordreCategorieRepository,
        DesordreCritereRepository $desordreCritereRepository,
        ZoneRepository $zoneRepository,
        SituationRepository $situationRepository,
        CritereRepository $critereRepository,
        SuiviSeenMarker $suiviSeenMarker,
        SignalementRepository $signalementRepository,
    ): Response {
        // load desordres data to prevent n+1 queries
        $desordreCategorieRepository->findAll();
        $desordreCritereRepository->findAll();
        $situationRepository->findAll();
        $critereRepository->findAll();
        /** @var User $user */
        $user = $this->getUser();
        if (SignalementStatus::ARCHIVED === $signalement->getStatut()) {
            $this->addFlash('error', "Ce signalement a été archivé et n'est pas consultable.");

            return $this->redirectToRoute('back_signalements_index');
        }
        $this->denyAccessUnlessGranted('SIGN_VIEW', $signalement);

        $eventDispatcher->dispatch(
            new SignalementViewedEvent($signalement, $user),
            SignalementViewedEvent::NAME
        );

        $canAnswerAffectation = $canCancelRefusedAffectation = false;
        $isAffectationRefused = $isAffectationAccepted = false;
        $isClosedForMe = null;
        $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
        if ($affectation = $signalement->getAffectations()->filter(function (Affectation $affectation) use ($partner) {
            return $affectation->getPartner() === $partner;
        })->first()) {
            switch ($affectation->getStatut()) {
                case AffectationStatus::ACCEPTED:
                    $isAffectationAccepted = true;
                    break;
                case AffectationStatus::REFUSED:
                    $isAffectationRefused = true;
                    $canCancelRefusedAffectation = $this->isGranted(AffectationVoter::ANSWER, $affectation);
                    break;
                case AffectationStatus::CLOSED:
                    $isClosedForMe = true;
                    break;
                case AffectationStatus::WAIT:
                    $canAnswerAffectation = $this->isGranted(AffectationVoter::ANSWER, $affectation);
                    break;
            }
        }

        $isClosedForMe = $isClosedForMe ?? SignalementStatus::CLOSED === $signalement->getStatut();
        $canValidateOrRefuseSignalement = $this->isGranted(SignalementVoter::VALIDATE, $signalement)
                                            && !$isClosedForMe
                                            && SignalementStatus::NEED_VALIDATION === $signalement->getStatut();
        $canReopenAffectation = $affectation && $this->isGranted(AffectationVoter::REOPEN, $affectation);

        $newSuiviToAdd = (new Suivi())->setSignalement($signalement);
        $addSuiviRoute = $this->generateUrl('back_signalement_add_suivi', ['uuid' => $signalement->getUuid()]);
        $addSuiviForm = $this->createForm(AddSuiviType::class, $newSuiviToAdd, ['action' => $addSuiviRoute]);

        $clotureFormRoute = $this->generateUrl('back_signalement_close_affectation', ['uuid' => $signalement->getUuid()]);
        $clotureForm = $this->createForm(ClotureType::class, (new SignalementAffectationClose())->setSignalement($signalement), ['action' => $clotureFormRoute]);

        $infoDesordres = $signalementDesordresProcessor->process($signalement);

        $canEditSignalement = $this->isGranted('ROLE_ADMIN');
        if (!$canEditSignalement && SignalementStatus::ACTIVE === $signalement->getStatut()) {
            $canEditSignalement = $this->isGranted('ROLE_ADMIN_TERRITORY') || $isAffectationAccepted;
        }
        $canEditClosedSignalement = SignalementStatus::CLOSED === $signalement->getStatut() && $this->isGranted('ROLE_ADMIN_TERRITORY');

        $signalementQualificationNDE = $signalementQualificationRepository->findOneBy([
            'signalement' => $signalement,
            'qualification' => Qualification::NON_DECENCE_ENERGETIQUE, ]);

        if (!$signalement->isV2()) {
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
        $suiviSeenMarker->markSeenByUsager($signalement);
        $signalementsOnSameAddress = [];
        if ($this->isGranted('ROLE_ADMIN_TERRITORY')) {
            $signalementsOnSameAddress = $signalementRepository->findOnSameAddress(
                signalement: $signalement,
                exclusiveStatus: [],
                excludedStatus: [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED, SignalementStatus::ARCHIVED]
            );
        }
        $twigParams = [
            'title' => '#'.$signalement->getReference().' Signalement',
            'situations' => $infoDesordres['criticitesArranged'],
            'photos' => $infoDesordres['photos'],
            'criteres' => $infoDesordres['criteres'],
            'canEditSignalement' => $canEditSignalement,
            'canEditClosedSignalement' => $canEditClosedSignalement,
            'canAnswerAffectation' => $canAnswerAffectation,
            'canCancelRefusedAffectation' => $canCancelRefusedAffectation,
            'canValidateOrRefuseSignalement' => $canValidateOrRefuseSignalement,
            'canReopenAffectation' => $canReopenAffectation,
            'affectation' => $affectation,
            'isAffectationAccepted' => $isAffectationAccepted,
            'isSignalementClosed' => SignalementStatus::CLOSED === $signalement->getStatut(),
            'isClosedForMe' => $isClosedForMe,
            'isAffectationRefused' => $isAffectationRefused,
            'isDanger' => $infoDesordres['isDanger'],
            'signalement' => $signalement,
            'partners' => $partners,
            'clotureForm' => $clotureForm,
            'addSuiviForm' => $addSuiviForm,
            'tags' => $tagsRepository->findAllActive($signalement->getTerritory()),
            'signalementQualificationNDE' => $signalementQualificationNDE,
            'signalementQualificationNDECriticite' => $signalementQualificationNDECriticites,
            'canEditNDE' => $canEditNDE,
            'listQualificationStatusesLabelsCheck' => $listQualificationStatusesLabelsCheck,
            'listConcludeProcedures' => $listConcludeProcedures,
            'partnersCanVisite' => $partnerVisite,
            'visites' => $interventionRepository->getOrderedVisitesForSignalement($signalement),
            'pendingVisites' => $interventionRepository->getPendingVisitesForSignalement($signalement),
            'allPhotosOrdered' => $allPhotosOrdered,
            'canTogglePartnerAffectation' => $this->isGranted(AffectationVoter::TOGGLE, $signalement),
            'canSeePartnerAffectation' => $this->isGranted(AffectationVoter::SEE, $signalement),
            'zones' => $zoneRepository->findZonesBySignalement($signalement),
            'signalementOnSameAddress' => $signalementsOnSameAddress,
        ];

        return $this->render('back/signalement/view.html.twig', $twigParams);
    }

    #[Route('/{uuid:signalement}/close-signalement-affectation', name: 'back_signalement_close_affectation', methods: 'POST')]
    public function closeAffectation(
        Signalement $signalement,
        Request $request,
        AffectationManager $affectationManager,
        SignalementManager $signalementManager,
        EventDispatcherInterface $eventDispatcher,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
        $affectation = $signalement->getAffectations()->filter(function (Affectation $affectation) use ($partner) {
            return $affectation->getPartner() === $partner;
        })->first();
        if (!$this->isGranted('SIGN_CLOSE', $signalement) && (!$affectation || !$this->isGranted('AFFECTATION_CLOSE', $affectation))) {
            return $this->json(['code' => Response::HTTP_FORBIDDEN, 'message' => 'Vous n\'êtes pas autorisé à fermer ce signalement ou cette affectation.'], Response::HTTP_FORBIDDEN);
        }
        $signalementAffectationClose = (new SignalementAffectationClose())->setSignalement($signalement);
        $clotureFormRoute = $this->generateUrl('back_signalement_close_affectation', ['uuid' => $signalement->getUuid()]);
        $form = $this->createForm(ClotureType::class, $signalementAffectationClose, ['action' => $clotureFormRoute]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $form, withPrefix: true)];

            return $this->json($response, $response['code']);
        }
        if ($form->isSubmitted()) {
            if (!$this->isGranted('ROLE_ADMIN_TERRITORY')) {
                $signalementAffectationClose->setIsPublic(false);
            }
            $signalementAffectationClose->setSubject($partner->getNom());

            $entity = $reference = null;
            if ('all' === $signalementAffectationClose->getType() && $this->isGranted('ROLE_ADMIN_TERRITORY')) {
                $signalementAffectationClose->setSubject('tous les partenaires');
                $entity = $signalement = $signalementManager->closeSignalementForAllPartners($signalementAffectationClose);
                $reference = $signalement->getReference();
                $eventDispatcher->dispatch(new SignalementClosedEvent($signalementAffectationClose), SignalementClosedEvent::NAME);
            /* @var Affectation $affectation */
            } elseif ($affectation) {
                $entity = $affectationManager->closeAffectation(
                    $affectation,
                    $user,
                    $signalementAffectationClose->getMotifCloture(),
                    $signalementAffectationClose->getDescription(),
                    true
                );
                $reference = $entity->getSignalement()->getReference();
            }

            if (!empty($entity)) {
                $this->addFlash('success', sprintf('Signalement #%s fermé avec succès !', $reference));
            }
            $signalementSearchQuery = $request->getSession()->get('signalementSearchQuery');
            $url = $signalementSearchQuery ? $this->generateUrl('back_signalements_index').$signalementSearchQuery->getQueryStringForUrl() : $this->generateUrl('back_signalements_index');

            return $this->redirect($url);
        }

        return $this->json(['code' => Response::HTTP_BAD_REQUEST]);
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
            $signalement->setStatut(SignalementStatus::ARCHIVED);
            $notificationRepository->deleteBySignalement($signalement);
            $affectationRepository->deleteByStatusAndSignalement(AffectationStatus::WAIT, $signalement);
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
