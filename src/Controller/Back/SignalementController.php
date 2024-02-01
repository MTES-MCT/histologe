<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Entity\Suivi;
use App\Entity\User;
use App\Event\SignalementClosedEvent;
use App\Event\SignalementViewedEvent;
use App\Form\ClotureType;
use App\Form\SignalementType;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Repository\AffectationRepository;
use App\Repository\CriticiteRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Repository\InterventionRepository;
use App\Repository\SignalementQualificationRepository;
use App\Repository\SituationRepository;
use App\Repository\TagRepository;
use App\Security\Voter\UserVoter;
use App\Service\FormHelper;
use App\Service\Signalement\CriticiteCalculator;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Service\Signalement\SignalementDesordresProcessor;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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

        $partners = $signalementManager->findAllPartners($signalement, true);

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

        return $this->render('back/signalement/view.html.twig', [
            'title' => 'Signalement',
            'createdFromDraft' => $signalement->getCreatedFrom(),
            'situations' => $infoDesordres['criticitesArranged'],
            'photos' => $infoDesordres['photos'],
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
            'isNewFormEnabled' => $parameterBag->get('feature_new_form'),
        ]);
    }

    private function isSignalementNDEActif(?SignalementQualification $signalementQualification): bool
    {
        if (null !== $signalementQualification) {
            return QualificationStatus::ARCHIVED != $signalementQualification->getStatus();
        }

        return false;
    }

    #[Route('/{uuid}/editer', name: 'back_signalement_edit', methods: ['GET', 'POST'])]
    public function editSignalement(
        Signalement $signalement,
        Request $request,
        ManagerRegistry $doctrine,
        SituationRepository $situationRepository,
        CriticiteCalculator $criticiteCalculator,
        SignalementQualificationUpdater $signalementQualificationUpdater,
        ParameterBagInterface $parameterBag,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);
        if (Signalement::STATUS_ACTIVE !== $signalement->getStatut()) {
            $this->addFlash('error', "Ce signalement n'est pas éditable.");

            return $this->redirectToRoute('back_index');
        }
        if ($parameterBag->get('feature_new_form')) {
            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }
        $title = 'Administration - Edition signalement #'.$signalement->getReference();
        $etats = ['Etat moyen', 'Mauvais état', 'Très mauvais état'];
        $etats_classes = ['moyen', 'grave', 'tres-grave'];
        $form = $this->createForm(SignalementType::class, $signalement);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $signalement->setModifiedBy($this->getUser());
                $signalement->setModifiedAt(new DateTimeImmutable());
                $signalement->setScore($criticiteCalculator->calculate($signalement));

                $signalementQualificationUpdater->updateQualificationFromScore($signalement);
                $suivi = new Suivi();
                $suivi->setCreatedBy($this->getUser());
                $suivi->setSignalement($signalement);
                $suivi->setIsPublic(false);
                $suivi->setDescription('Modification du signalement par un partenaire');
                $suivi->setType(SUIVI::TYPE_AUTO);
                $doctrine->getManager()->persist($suivi);
                $signalement->setGeoloc($form->getExtraData()['geoloc']);
                $signalement->setInseeOccupant($form->getExtraData()['inseeOccupant']);
                $doctrine->getManager()->persist($signalement);
                $doctrine->getManager()->flush();
                $this->addFlash('success', 'Signalement modifié avec succès !');

                return $this->json(['response' => 'success_edited']);
            }

            return $this->json(
                [
                    'response' => 'formErrors',
                    'errsMsgList' => FormHelper::getErrorsFromForm($form),
                ],
            );
        }

        return $this->render('back/signalement/edit.html.twig', [
            'title' => $title,
            'form' => $form->createView(),
            'signalement' => $signalement,
            'situations' => $situationRepository->findAllActive(),
            'etats' => $etats,
            'etats_classes' => $etats_classes,
        ]);
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
}
