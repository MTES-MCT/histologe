<?php

namespace App\Controller\Back;

use App\Dto\AcceptSignalement;
use App\Dto\AgentSelection;
use App\Dto\RefusAffectation;
use App\Dto\RefusSignalement;
use App\Dto\SignalementAffectationClose;
use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\DocumentType;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Event\SignalementClosedEvent;
use App\Event\SignalementViewedEvent;
use App\Factory\SignalementSearchQueryFactory;
use App\Form\AcceptSignalementType;
use App\Form\AddSuiviType;
use App\Form\AgentSelectionType;
use App\Form\ClotureType;
use App\Form\RefusAffectationType;
use App\Form\RefusSignalementType;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Repository\AffectationRepository;
use App\Repository\CritereRepository;
use App\Repository\CriticiteRepository;
use App\Repository\DesordreCategorieRepository;
use App\Repository\DesordreCritereRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Repository\FileRepository;
use App\Repository\InterventionRepository;
use App\Repository\NotificationRepository;
use App\Repository\SignalementQualificationRepository;
use App\Repository\SignalementRepository;
use App\Repository\SituationRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Repository\ZoneRepository;
use App\Security\Voter\AffectationVoter;
use App\Security\Voter\SignalementVoter;
use App\Service\EmailAlertChecker;
use App\Service\FormHelper;
use App\Service\Signalement\PhotoHelper;
use App\Service\Signalement\SignalementDesordresProcessor;
use App\Service\Signalement\SuiviSeenMarker;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/bo/signalements')]
class SignalementController extends AbstractController
{
    public function __construct(private EmailAlertChecker $emailAlertBuilder)
    {
    }

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
        FileRepository $fileRepository,
        UserRepository $userRepository,
        SuiviSeenMarker $suiviSeenMarker,
        UserSignalementSubscriptionRepository $signalementSubscriptionRepository,
        SignalementRepository $signalementRepository,
        UrlGeneratorInterface $urlGenerator,
        #[Autowire(env: 'FEATURE_NEW_DOCUMENT_SPACE')]
        bool $featureNewDocumentSpace,
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

        $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
        $affectation = $signalement->getAffectationForPartner($partner);
        $canAnswerAffectation = $this->isGranted(AffectationVoter::AFFECTATION_ACCEPT_OR_REFUSE, $affectation);
        $canCancelRefusedAffectation = $this->isGranted(AffectationVoter::AFFECTATION_CANCEL_REFUSED, $affectation);
        $isUserSubscribed = $signalementSubscriptionRepository->findOneBy(['user' => $user, 'signalement' => $signalement]) ? true : false;

        $acceptSignalementForm = null;
        $refusSignalementForm = null;
        $isUniqueRtInCurrentPartner = false;
        if ($this->isGranted(SignalementVoter::VALIDATE, $signalement)) {
            if ($user->isTerritoryAdmin()) {
                if (1 === count($userRepository->findActiveTerritoryAdminsInPartner($partner))) {
                    $isUniqueRtInCurrentPartner = true;
                }
            }
            if (!$isUniqueRtInCurrentPartner) {
                $acceptSignalement = (new AcceptSignalement())->setSignalement($signalement)->setAgents([$user]);
                $acceptSignalementFormRoute = $this->generateUrl('back_signalement_accept_post', ['uuid' => $signalement->getUuid()]);
                $acceptSignalementForm = $this->createForm(
                    AcceptSignalementType::class,
                    $acceptSignalement,
                    ['action' => $acceptSignalementFormRoute]
                );
            }
            $refusSignalement = (new RefusSignalement())->setSignalement($signalement);
            $refusSignalementRoute = $this->generateUrl('back_signalement_deny', ['uuid' => $signalement->getUuid()]);
            $refusSignalementForm = $this->createForm(RefusSignalementType::class, $refusSignalement, ['action' => $refusSignalementRoute]);
        }

        $signalementAffectationClose = (new SignalementAffectationClose())->setSignalement($signalement);
        $clotureFormRoute = $this->generateUrl('back_signalement_close_affectation', ['uuid' => $signalement->getUuid()]);
        $clotureForm = $this->createForm(ClotureType::class, $signalementAffectationClose, ['action' => $clotureFormRoute]);

        $newSuiviToAdd = (new Suivi())->setSignalement($signalement);
        $addSuiviRoute = $this->generateUrl('back_signalement_add_suivi', ['uuid' => $signalement->getUuid()]);
        $addSuiviForm = $this->createForm(AddSuiviType::class, $newSuiviToAdd, ['action' => $addSuiviRoute]);

        $refusAffectationForm = null;
        if ($canAnswerAffectation && $affectation) {
            $refusAffectation = (new RefusAffectation())->setSignalement($signalement);
            $refusAffectationFormRoute = $this->generateUrl('back_signalement_affectation_deny', ['affectation' => $affectation->getId()]);
            $refusAffectationForm = $this->createForm(RefusAffectationType::class, $refusAffectation, ['action' => $refusAffectationFormRoute]);
        }

        $acceptAffectationForm = null;
        if (($canAnswerAffectation || $canCancelRefusedAffectation) && $affectation) {
            $acceptAffectation = (new AgentSelection())->setAffectation($affectation)->setAgents([$user]);
            $acceptAffectationFormRoute = $this->generateUrl('back_signalement_affectation_accept', ['affectation' => $affectation->getId()]);
            $acceptAffectationForm = $this->createForm(
                AgentSelectionType::class,
                $acceptAffectation,
                ['action' => $acceptAffectationFormRoute]
            );
        }

        $transferSubscriptionForm = null;
        if ($isUserSubscribed && $affectation) {
            $transferSubscription = (new AgentSelection())->setAffectation($affectation)->setAgents([$user]);
            $transferSubscriptionFormRoute = $this->generateUrl('back_signalement_unsubscribe', ['uuid' => $signalement->getUuid()]);
            $transferSubscriptionForm = $this->createForm(
                AgentSelectionType::class,
                $transferSubscription,
                [
                    'action' => $transferSubscriptionFormRoute,
                    'exclude_user' => $this->getUser(),
                    'label' => 'Sélectionnez le(s) agent(s) à qui transmettre le dossier',
                ]
            );
        }

        $infoDesordres = $signalementDesordresProcessor->process($signalement);

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

        $partners = $signalementManager->findAffectablePartners(
            signalement: $signalement,
            filterInjonctionBailleur: SignalementStatus::INJONCTION_BAILLEUR === $signalement->getStatut()
        );

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
        $linkToVisitGrid = false;
        if ($featureNewDocumentSpace) {
            $existingVisitGrid = $fileRepository->findOneBy([
                'territory' => $signalement->getTerritory(),
                'documentType' => DocumentType::GRILLE_DE_VISITE,
            ]);
            if ($existingVisitGrid) {
                $linkToVisitGrid = $urlGenerator->generate('show_file', ['uuid' => $existingVisitGrid->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        } elseif (!$signalement->getTerritory()->getIsGrilleVisiteDisabled()) {
            $linkToVisitGrid = $this->generateUrl('back_territory_grille_visite', ['territory' => $signalement->getTerritory()->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $allPhotosOrdered = PhotoHelper::getSortedPhotos($signalement);
        $suiviSeenMarker->markSeenByUsager($signalement);
        $signalementsOnSameAddress = $signalementRepository->findOnSameAddress(
            signalement: $signalement,
            exclusiveStatus: [],
            excludedStatus: SignalementStatus::excludedStatuses(),
        );
        $subscriptionsInMyPartner = $signalementSubscriptionRepository->findForSignalementAndPartner($signalement, $partner);

        $twigParams = [
            'title' => '#'.$signalement->getReference().' Signalement',
            'situations' => $infoDesordres['criticitesArranged'],
            'photos' => $infoDesordres['photos'],
            'criteres' => $infoDesordres['criteres'],
            'affectation' => $affectation,
            'isDanger' => $infoDesordres['isDanger'],
            'signalement' => $signalement,
            'partner' => $partner,
            'partners' => $partners,
            'clotureForm' => $clotureForm,
            'addSuiviForm' => $addSuiviForm,
            'acceptSignalementForm' => $acceptSignalementForm,
            'refusSignalementForm' => $refusSignalementForm,
            'acceptAffectationForm' => $acceptAffectationForm,
            'refusAffectationForm' => $refusAffectationForm,
            'transferSubscriptionForm' => $transferSubscriptionForm,
            'tags' => $tagsRepository->findAllActive($signalement->getTerritory()),
            'signalementQualificationNDE' => $signalementQualificationNDE,
            'signalementQualificationNDECriticite' => $signalementQualificationNDECriticites,
            'listQualificationStatusesLabelsCheck' => $listQualificationStatusesLabelsCheck,
            'listConcludeProcedures' => $listConcludeProcedures,
            'partnersCanVisite' => $partnerVisite,
            'visites' => $interventionRepository->getOrderedVisitesForSignalement($signalement),
            'pendingVisites' => $interventionRepository->getPendingVisitesForSignalement($signalement),
            'linkToVisitGrid' => $linkToVisitGrid,
            'allPhotosOrdered' => $allPhotosOrdered,
            'zones' => $zoneRepository->findZonesBySignalement($signalement),
            'signalementsOnSameAddress' => $signalementsOnSameAddress,
            'isUserSubscribed' => $isUserSubscribed,
            'subscriptionsInMyPartner' => $subscriptionsInMyPartner,
            'partnerEmailAlerts' => $this->emailAlertBuilder->buildPartnerEmailAlert($signalement),
            'isUniqueRtInCurrentPartner' => $isUniqueRtInCurrentPartner,
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
        SignalementSearchQueryFactory $signalementSearchQueryFactory,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
        $affectation = $signalement->getAffectations()->filter(function (Affectation $affectation) use ($partner) {
            return $affectation->getPartner() === $partner;
        })->first();
        if (!$this->isGranted('SIGN_CLOSE', $signalement) && (!$affectation || !$this->isGranted(AffectationVoter::AFFECTATION_CLOSE, $affectation))) {
            return $this->json(['code' => Response::HTTP_FORBIDDEN, 'message' => 'Vous n\'êtes pas autorisé à fermer ce signalement ou cette affectation.'], Response::HTTP_FORBIDDEN);
        }

        $signalementAffectationClose = (new SignalementAffectationClose())->setSignalement($signalement);
        $clotureFormRoute = $this->generateUrl('back_signalement_close_affectation', ['uuid' => $signalement->getUuid()]);
        $form = $this->createForm(ClotureType::class, $signalementAffectationClose, ['action' => $clotureFormRoute]);
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            return $this->json(['code' => Response::HTTP_BAD_REQUEST]);
        }
        if (!$form->isValid()) {
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $form, withPrefix: true)];

            return $this->json($response, $response['code']);
        }
        if (!$this->isGranted('ROLE_ADMIN_TERRITORY')) {
            $signalementAffectationClose->setIsPublic(false);
        }
        $signalementAffectationClose->setSubject($partner->getNom());

        $entity = $reference = null;
        if ('all' === $signalementAffectationClose->getType() && $this->isGranted('ROLE_ADMIN_TERRITORY')) {
            $signalementAffectationClose->setSubject('tous les partenaires');
            $entity = $signalement = $signalementManager->closeSignalementForAllPartners($signalementAffectationClose, $partner);
            $reference = $signalement->getReference();
            $eventDispatcher->dispatch(new SignalementClosedEvent($signalementAffectationClose, $partner), SignalementClosedEvent::NAME);
        /* @var Affectation $affectation */
        } elseif ($affectation) {
            $entity = $affectationManager->closeAffectation(
                affectation: $affectation,
                user: $user,
                motif: $signalementAffectationClose->getMotifCloture(),
                partner: $partner,
                message: $signalementAffectationClose->getDescription(),
                files: $signalementAffectationClose->getFiles(),
                flush: true
            );
            $reference = $entity->getSignalement()->getReference();
        }

        if (!empty($entity)) {
            $this->addFlash('success', sprintf('Signalement #%s fermé avec succès !', $reference));
        }
        $signalementSearchQuery = $signalementSearchQueryFactory->createFromCookie($request);

        if (SignalementStatus::INJONCTION_BAILLEUR === $signalement->getStatut()) {
            $url = $this->generateUrl('back_injonction_signalement_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        } else {
            $url = $this->generateUrl('back_signalements_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $url .= $signalementSearchQuery?->getQueryStringForUrl();
        }

        return $this->json(['redirect' => true, 'url' => $url]);
    }

    #[Route('/{uuid:signalement}/supprimer', name: 'back_signalement_delete', methods: 'POST')]
    public function newDeleteSignalement(
        Signalement $signalement,
        Request $request,
        ManagerRegistry $doctrine,
        AffectationManager $affectationManager,
        NotificationRepository $notificationRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('SIGN_DELETE', $signalement);
        if ($this->isCsrfTokenValid(
            'signalement_delete_'.$signalement->getId(),
            (string) $request->getPayload()->get('_token')
        )
        ) {
            $signalement->setStatut(SignalementStatus::ARCHIVED);
            $notificationRepository->deleteBySignalement($signalement);
            $affectationManager->removeAffectationsBySignalement($signalement, AffectationStatus::WAIT);

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
        if (!$this->isGranted('SIGN_EDIT_ACTIVE', $signalement) && !$this->isGranted('SIGN_EDIT_CLOSED', $signalement)) {
            throw $this->createAccessDeniedException();
        }
        if (
            $this->isCsrfTokenValid('signalement_save_tags', (string) $request->request->get('_token'))
        ) {
            $tagIds = $request->request->get('tag-ids');
            $tagList = explode(',', (string) $tagIds);
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

    #[Route('/{uuid:signalement}/list-all-photo-situation', name: 'back_signalement_list_all_photo_situation')]
    public function listAllPhotoSituation(
        Signalement $signalement,
        SignalementDesordresProcessor $signalementDesordresProcessor,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('SIGN_VIEW', $signalement);
        $infoDesordres = $signalementDesordresProcessor->process($signalement);

        $html = $this->renderView('back/signalement/view/photos-documents.html.twig', [
            'signalement' => $signalement,
            'criteres' => $infoDesordres['criteres'],
            'zonetitle' => null,
            'filesFilter' => 'situation',
            'listAllPhotos' => true,
        ]);

        return $this->json(['html' => $html]);
    }
}
