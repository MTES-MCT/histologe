<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Entity\User;
use App\Event\SignalementClosedEvent;
use App\Event\SignalementViewedEvent;
use App\Form\ClotureType;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Repository\AffectationRepository;
use App\Repository\CriticiteRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Repository\InterventionRepository;
use App\Repository\SignalementQualificationRepository;
use App\Repository\TagRepository;
use App\Security\Voter\UserVoter;
use App\Service\Signalement\PhotoHelper;
use App\Service\Signalement\SignalementDesordresProcessor;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/signalements')]
class SignalementController extends AbstractController
{
    #[Route('/{uuid}', name: 'back_signalement_view')]
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
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $this->denyAccessUnlessGranted('SIGN_VIEW', $signalement);
        if (Signalement::STATUS_ARCHIVED === $signalement->getStatut()) {
            $this->addFlash('error', "Ce signalement a été archivé et n'est pas consultable.");

            return $this->redirectToRoute('back_index');
        }

        $eventDispatcher->dispatch(
            new SignalementViewedEvent($signalement, $user),
            SignalementViewedEvent::NAME
        );

        $isRefused = $isAccepted = $isClosedForMe = null;
        if ($isAffected = $signalement->getAffectations()->filter(function (Affectation $affectation) use ($user) {
            return $affectation->getPartner() === $user->getPartner();
        })->first()) {
            switch ($isAffected->getStatut()) {
                case Affectation::STATUS_ACCEPTED:
                    $isAccepted = $isAffected;
                    break;
                case Affectation::STATUS_REFUSED:
                    $isRefused = $isAffected;
                    break;
                case Affectation::STATUS_CLOSED:
                    $isClosedForMe = true;
                    break;
            }
        }
        $isClosedForMe = $isClosedForMe ?? Signalement::STATUS_CLOSED === $signalement->getStatut();
        $clotureForm = $this->createForm(ClotureType::class);
        $clotureForm->handleRequest($request);
        $params = [];
        if ($clotureForm->isSubmitted() && $clotureForm->isValid()) {
            $params['motif_cloture'] = $clotureForm->get('motif')->getData();
            $params['motif_suivi'] = $clotureForm->getExtraData()['suivi'];
            $params['suivi_public'] = false;
            if ($this->isGranted('ROLE_ADMIN_TERRITORY') && isset($clotureForm->getExtraData()['publicSuivi'])) {
                $params['suivi_public'] = $clotureForm->getExtraData()['publicSuivi'];
            }
            $params['subject'] = $user?->getPartner()?->getNom();
            $params['closed_for'] = $clotureForm->get('type')->getData();

            $entity = null;
            if ('all' === $params['closed_for']) {
                $params['subject'] = 'tous les partenaires';
                $entity = $signalement = $signalementManager->closeSignalementForAllPartners(
                    $signalement,
                    $params['motif_cloture']
                );

                /* @var Affectation $isAffected */
            } elseif ($isAffected) {
                $entity = $affectationManager->closeAffectation($isAffected, $user, $params['motif_cloture'], true);
            }

            if (!empty($entity)) {
                $eventDispatcher->dispatch(new SignalementClosedEvent($entity, $params), SignalementClosedEvent::NAME);
                $this->addFlash('success', 'Signalement cloturé avec succès !');
            }

            return $this->redirectToRoute('back_index');
        }
        $infoDesordres = $signalementDesordresProcessor->process($signalement);

        $canEditSignalement = false;
        if (
            Signalement::STATUS_ACTIVE === $signalement->getStatut()
            || Signalement::STATUS_NEED_PARTNER_RESPONSE === $signalement->getStatut()
        ) {
            $canEditSignalement = $this->isGranted('ROLE_ADMIN')
                || $this->isGranted('ROLE_ADMIN_TERRITORY')
                || $isAccepted;
        }
        $canExportSignalement = $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_ADMIN_TERRITORY')
            || $isAffected;

        $signalementQualificationNDE = $signalementQualificationRepository->findOneBy([
            'signalement' => $signalement,
            'qualification' => Qualification::NON_DECENCE_ENERGETIQUE, ]);
        $isSignalementNDEActif = $this->isSignalementNDEActif($signalementQualificationNDE);

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

        $canEditNDE = $isSignalementNDEActif && $this->isGranted(UserVoter::SEE_NDE, $this->getUser())
        && $canEditSignalement;

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

        return $this->render('back/signalement/view.html.twig', [
            'title' => 'Signalement',
            'createdFromDraft' => $signalement->getCreatedFrom(),
            'situations' => $infoDesordres['criticitesArranged'],
            'photos' => $infoDesordres['photos'],
            'criteres' => $infoDesordres['criteres'],
            'needValidation' => Signalement::STATUS_NEED_VALIDATION === $signalement->getStatut(),
            'canEditSignalement' => $canEditSignalement,
            'canExportSignalement' => $canExportSignalement,
            'isAffected' => $isAffected,
            'isAccepted' => $isAccepted,
            'isClosed' => Signalement::STATUS_CLOSED === $signalement->getStatut(),
            'isClosedForMe' => $isClosedForMe,
            'isRefused' => $isRefused,
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
        ]);
    }

    private function isSignalementNDEActif(?SignalementQualification $signalementQualification): bool
    {
        if (null !== $signalementQualification) {
            return QualificationStatus::ARCHIVED != $signalementQualification->getStatus();
        }

        return false;
    }

    #[Route('/{uuid}/supprimer', name: 'back_signalement_delete', methods: 'POST')]
    public function deleteSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('SIGN_DELETE', $signalement);
        if ($this->isCsrfTokenValid('signalement_delete_'.$signalement->getId(), $request->get('_token'))) {
            $signalement->setStatut(Signalement::STATUS_ARCHIVED);
            $doctrine->getManager()->persist($signalement);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Signalement supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Une erreur est survenu lors de la suppression');
        }

        return $this->redirectToRoute('back_index');
    }

    #[Route('/v2/{uuid}/supprimer', name: 'back_v2_signalement_delete', methods: 'POST')]
    public function newDeleteSignalement(
        Signalement $signalement,
        Request $request,
        ManagerRegistry $doctrine
    ): JsonResponse {
        $this->denyAccessUnlessGranted('SIGN_DELETE', $signalement);
        if ($this->isCsrfTokenValid(
            'signalement_delete_'.$signalement->getId(),
            $request->getPayload()->get('_token')
        )
        ) {
            $signalement->setStatut(Signalement::STATUS_ARCHIVED);
            $doctrine->getManager()->persist($signalement);
            $doctrine->getManager()->flush();
            $response = [
                'status' => Response::HTTP_OK,
                'message' => sprintf('Le signalement %s a bien été supprimé.', $signalement->getReference()),
            ];
        } else {
            $response = [
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Une erreur s\'est produite lors de la suppression. Veuillez réessayer plus tard.',
            ];
        }

        return $this->json($response, $response['status']);
    }
}
