<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Entity\Situation;
use App\Entity\Suivi;
use App\Event\SignalementClosedEvent;
use App\Event\SignalementViewedEvent;
use App\Form\ClotureType;
use App\Form\SignalementType;
use App\Manager\SignalementManager;
use App\Repository\CritereRepository;
use App\Repository\CriticiteRepository;
use App\Repository\SignalementQualificationRepository;
use App\Repository\SituationRepository;
use App\Repository\TagRepository;
use App\Service\Signalement\CriticiteCalculatorService;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/bo/signalements')]
class BackSignalementController extends AbstractController
{
    #[Route('/{uuid}', name: 'back_signalement_view')]
    public function viewSignalement(
        Signalement $signalement,
        Request $request,
        TagRepository $tagsRepository,
        SignalementManager $signalementManager,
        EventDispatcherInterface $eventDispatcher,
        ParameterBagInterface $parameterBag,
        SignalementQualificationRepository $signalementQualificationRepository,
        CriticiteRepository $criticiteRepository
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_VIEW', $signalement);
        if (Signalement::STATUS_ARCHIVED === $signalement->getStatut()) {
            $this->addFlash('error', "Ce signalement à été archivé et n'est pas consultable.");

            return $this->redirectToRoute('back_index');
        }

        $eventDispatcher->dispatch(
            new SignalementViewedEvent($signalement, $this->getUser()),
            SignalementViewedEvent::NAME
        );

        $isRefused = $isAccepted = $isClosedForMe = null;
        if ($isAffected = $signalement->getAffectations()->filter(function (Affectation $affectation) {
            return $affectation->getPartner() === $this->getUser()->getPartner();
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
            $params['subject'] = $this->getUser()?->getPartner()?->getNom();
            $params['closed_for'] = $clotureForm->get('type')->getData();

            if ('all' === $params['closed_for']) {
                $params['subject'] = 'tous les partenaires';
                $entity = $signalement = $signalementManager->closeSignalementForAllPartners(
                    $signalement,
                    $params['motif_cloture']
                );
            }

            /** @var Affectation $isAffected */
            if ($isAffected) {
                $entity = $signalementManager->closeAffectation($isAffected, $params['motif_cloture']);
            }

            $eventDispatcher->dispatch(new SignalementClosedEvent($entity, $params), SignalementClosedEvent::NAME);
            $this->addFlash('success', 'Signalement cloturé avec succès !');

            return $this->redirectToRoute('back_index');
        }
        $criticitesArranged = [];
        foreach ($signalement->getCriticites() as $criticite) {
            $criticitesArranged[$criticite->getCritere()->getSituation()->getLabel()][$criticite->getCritere()->getLabel()] = $criticite;
        }

        $canEditSignalement = false;
        if (Signalement::STATUS_ACTIVE === $signalement->getStatut() || Signalement::STATUS_NEED_PARTNER_RESPONSE === $signalement->getStatut()) {
            $canEditSignalement = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_TERRITORY') || $isAccepted;
        }
        $canExportSignalement = $canEditSignalement;
        if (!$canExportSignalement && Signalement::STATUS_CLOSED === $signalement->getStatut()) {
            $canExportSignalement = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_TERRITORY') || $isAccepted;
        }

        $experimentationTerritories = $parameterBag->get('experimentation_territory');
        $isExperimentationTerritory = \array_key_exists($signalement->getTerritory()->getZip(), $experimentationTerritories);

        $signalementQualificationNDE = $signalementQualificationRepository->findOneBy([
            'signalement' => $signalement,
            'qualification' => Qualification::NON_DECENCE_ENERGETIQUE, ]);
        $isSignalementNDEActif = $this->isSignalementNDEActif($signalementQualificationNDE);
        $signalementQualificationNDECriticites = $signalementQualificationNDE ? $criticiteRepository->findBy(['id' => $signalementQualificationNDE->getCriticites()]) : null;

        $partners = $signalementManager->findAllPartners($signalement, $isExperimentationTerritory && $isSignalementNDEActif);

        $files = $parameterBag->get('files');

        return $this->render('back/signalement/view.html.twig', [
            'title' => 'Signalement',
            'situations' => $criticitesArranged,
            'needValidation' => Signalement::STATUS_NEED_VALIDATION === $signalement->getStatut(),
            'canEditSignalement' => $canEditSignalement,
            'canExportSignalement' => $canExportSignalement,
            'isAffected' => $isAffected,
            'isAccepted' => $isAccepted,
            'isClosed' => Signalement::STATUS_CLOSED === $signalement->getStatut(),
            'isClosedForMe' => $isClosedForMe,
            'isRefused' => $isRefused,
            'signalement' => $signalement,
            'partners' => $partners,
            'clotureForm' => $clotureForm->createView(),
            'tags' => $tagsRepository->findAllActive($signalement->getTerritory()),
            'isExperimentationTerritory' => $isExperimentationTerritory,
            'isSignalementNDE' => $isSignalementNDEActif,
            'signalementQualificationNDE' => $signalementQualificationNDE,
            'signalementQualificationNDECriticite' => $signalementQualificationNDECriticites,
            'files' => $files,
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
        CritereRepository $critereRepository,
        HttpClientInterface $httpClient
    ): Response {
        $title = 'Administration - Edition signalement #'.$signalement->getReference();
        $etats = ['Etat moyen', 'Mauvais état', 'Très mauvais état'];
        $etats_classes = ['moyen', 'grave', 'tres-grave'];
        $form = $this->createForm(SignalementType::class, $signalement);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $signalement->setModifiedBy($this->getUser());
                $signalement->setModifiedAt(new DateTimeImmutable());
                $score = new CriticiteCalculatorService($signalement, $critereRepository);
                $signalement->setScoreCreation($score->calculate());
                $signalement->setNewScoreCreation($score->calculateNewCriticite());
                $data = [];
                if (\array_key_exists('situation', $form->getExtraData())) {
                    $data['situation'] = $form->getExtraData()['situation'];
                }
                if ($data['situation']) {
                    foreach ($data['situation'] as $idSituation => $criteres) {
                        $situation = $doctrine->getManager()->getRepository(Situation::class)->find($idSituation);
                        $signalement->addSituation($situation);
                        $data['situation'][$idSituation]['label'] = $situation->getLabel();
                        foreach ($criteres as $critere) {
                            foreach ($critere as $idCritere => $criticites) {
                                $critere = $doctrine->getManager()->getRepository(Critere::class)->find($idCritere);
                                $signalement->addCritere($critere);
                                $data['situation'][$idSituation]['critere'][$idCritere]['label'] = $critere->getLabel();
                                $criticite = $doctrine->getManager()->getRepository(Criticite::class)->find($data['situation'][$idSituation]['critere'][$idCritere]['criticite']);
                                $signalement->addCriticite($criticite);
                                $data['situation'][$idSituation]['critere'][$idCritere]['criticite'] = [$criticite->getId() => ['label' => $criticite->getLabel(), 'score' => $criticite->getScore()]];
                            }
                        }
                    }
                }
                !empty($data['situation']) && $signalement->setJsonContent($data['situation']);
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
                $this->addFlash('success', 'Signalement modifié avec succés !');

                return $this->json(['response' => 'success_edited']);
            }

            return $this->json(['response' => $form->getErrors()]);
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
