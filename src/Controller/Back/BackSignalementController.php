<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\Situation;
use App\Entity\Suivi;
use App\Event\SignalementClosedEvent;
use App\Form\ClotureType;
use App\Form\SignalementType;
use App\Manager\SignalementManager;
use App\Repository\PartnerRepository;
use App\Repository\SituationRepository;
use App\Repository\TagRepository;
use App\Service\CriticiteCalculatorService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/bo/s')]
class BackSignalementController extends AbstractController
{
    #[Route('/{uuid}', name: 'back_signalement_view')]
    public function viewSignalement(Signalement $signalement,
                                    Request $request,
                                    EntityManagerInterface $entityManager,
                                    TagRepository $tagsRepository,
                                    PartnerRepository $partnerRepository,
                                    SignalementManager $signalementManager,
                                    EventDispatcherInterface $eventDispatcher
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_VIEW', $signalement);
        if (Signalement::STATUS_ARCHIVED === $signalement->getStatut()) {
            $this->addFlash('error', "Ce signalement à été archivé et n'est pas consultable.");

            return $this->redirectToRoute('back_index');
        }

        // TODO REPLACE THIS
        $this->getUser()->getNotifications()->filter(function (Notification $notification) use ($signalement, $entityManager) {
            if ($notification->getSignalement()->getId() === $signalement->getId()) {
                $notification->setIsSeen(true);
                $entityManager->persist($notification);
            }
        });
        $entityManager->flush();
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
            $params['closed_for'] = 'partner';

            if ('all' === $params['closed_for'] = $clotureForm->get('type')->getData()) {
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
            'partners' => $signalementManager->findAllPartners($signalement),
            'clotureForm' => $clotureForm->createView(),
            'tags' => $tagsRepository->findAllActive($signalement->getTerritory()),
        ]);
    }

    #[Route('/{uuid}/edit', name: 'back_signalement_edit', methods: ['GET', 'POST'])]
    public function editSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine, SituationRepository $situationRepository, HttpClientInterface $httpClient): Response
    {
        $title = 'Administration - Edition signalement #'.$signalement->getReference();
        $etats = ['Etat moyen', 'Mauvais état', 'Très mauvais état'];
        $etats_classes = ['moyen', 'grave', 'tres-grave'];
        $form = $this->createForm(SignalementType::class, $signalement);
        $form->handleRequest($request);
        if ($form->isSubmitted() /* && $form->isValid() */) {
            // TODO INSEE AP
            $signalement->setModifiedBy($this->getUser());
            $signalement->setModifiedAt(new DateTimeImmutable());
            $score = new CriticiteCalculatorService($signalement, $doctrine);
            $signalement->setScoreCreation($score->calculate());
            $data = [];
            $data['situation'] = $form->getExtraData()['situation'];
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
            !empty($data['situation']) && $signalement->setJsonContent($data['situation']);
            $suivi = new Suivi();
            $suivi->setCreatedBy($this->getUser());
            $suivi->setSignalement($signalement);
            $suivi->setIsPublic(false);
            $suivi->setDescription('Modification du signalement par un partenaire');
            $doctrine->getManager()->persist($suivi);
            /*if (!$signalement->getInseeOccupant() || !isset($signalement->getGeoloc()['lat']) || !isset($signalement->getGeoloc()['lat'])) {
                $adresse = $signalement->getAdresseOccupant() . ' ' . $signalement->getCpOccupant() . ' ' . $signalement->getVilleOccupant();
                $response = json_decode($httpClient->request('GET', 'https://api-adresse.data.gouv.fr/search/?q=' . $adresse)->getContent(), true);
                if (!empty($response['features'][0])) {
                    $coordinates = $response['features'][0]['geometry']['coordinates'];
                    $insee = $response['features'][0]['properties']['citycode'];
                    if ($coordinates)
                        $signalement->setGeoloc(['lat' => $coordinates[0], 'lng' => $coordinates[1]]);
                    if ($insee)
                        $signalement->setInseeOccupant($insee);
                }
            }*/
            $signalement->setGeoloc($form->getExtraData()['geoloc']);
            $signalement->setInseeOccupant($form->getExtraData()['inseeOccupant']);
            $doctrine->getManager()->persist($signalement);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Signalement modifié avec succés !');

            return $this->json(['response' => 'success_edited']);
        } elseif ($form->isSubmitted()) {
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

    #[Route('/{uuid}/delete', name: 'back_signalement_delete', methods: 'POST')]
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
