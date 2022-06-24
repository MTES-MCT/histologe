<?php

namespace App\Controller\Back;


use App\Entity\Affectation;
use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\Situation;
use App\Entity\Suivi;
use App\Form\ClotureType;
use App\Form\SignalementType;
use App\Repository\PartnerRepository;
use App\Repository\SituationRepository;
use App\Repository\TagRepository;
use App\Service\CriticiteCalculatorService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/bo/s')]
class BackSignalementController extends AbstractController
{

    #[Route('/{uuid}', name: 'back_signalement_view')]
    public function viewSignalement($uuid, Request $request, EntityManagerInterface $entityManager, TagRepository $tagsRepository, PartnerRepository $partnerRepository): Response
    {
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findByUuid($uuid);
        $this->denyAccessUnlessGranted('SIGN_VIEW', $signalement);
        if ($signalement->getStatut() === Signalement::STATUS_ARCHIVED) {
            $this->addFlash("error", "Ce signalement à été archivé et n'est pas consultable.");
            return $this->redirectToRoute('back_index');
        }

        //TODO REPLACE THIS
        $this->getUser()->getNotifications()->filter(function (Notification $notification) use ($signalement, $entityManager) {
            if ($notification->getSignalement()->getId() === $signalement->getId()) {
                $notification->setIsSeen(true);
                $entityManager->persist($notification);
            }
        });
        $entityManager->flush();
        $isRefused = $isAccepted = $isClosedForMe = null;
        if ($isAffected = $signalement->getAffectations()->filter(function (Affectation $affectation) use ($isClosedForMe) {
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
        $isClosedForMe = $isClosedForMe ?? $signalement->getStatut() === Signalement::STATUS_CLOSED;
        $clotureForm = $this->createForm(ClotureType::class);
        $clotureForm->handleRequest($request);
        if ($clotureForm->isSubmitted() && $clotureForm->isValid()) {
            $motifCloture = $clotureForm->get('motif')->getData();
            $motifSuivi = $clotureForm->getExtraData()['suivi'];
            $sujet = $this->getUser()?->getPartner()?->getNom();
            if ($clotureForm->get('type')->getData() === 'all') {
                $signalement->setStatut(Signalement::STATUS_CLOSED);
                $signalement->setMotifCloture($motifCloture);
                $signalement->setClosedAt(new DateTimeImmutable());
                $sujet = 'tous les partners';
                $signalement->getAffectations()->map(function (Affectation $affectation) use ($entityManager,$motifCloture) {
                    $affectation->setStatut(Affectation::STATUS_CLOSED);
                    $affectation->setMotifCloture($motifCloture);
                    $affectation->setAnsweredBy($this->getUser());
                    /*   $entityManager->getConnection()->connect();*/
                    $entityManager->persist($affectation);
                });
            }
            $motifSuivi = preg_replace('/<p[^>]*>/', '', $motifSuivi); // Remove the start <p> or <p attr="">
            $motifSuivi = str_replace('</p>', '<br>', $motifSuivi); // Replace the end
            $suivi = new Suivi();
            $suivi->setDescription('Le signalement à été cloturé pour ' . $sujet . ' avec le motif suivant: <br> <strong>' . $motifCloture . '</strong><br><strong>Desc.: </strong>' . $motifSuivi);
            $suivi->setCreatedBy($this->getUser());
            $signalement->addSuivi($suivi);
            /** @var Affectation $isAffected */
            if ($isAffected) {
                $isAffected->setStatut(Affectation::STATUS_CLOSED);
                $isAffected->setAnsweredAt(new DateTimeImmutable());
                $isAffected->setMotifCloture($motifCloture);
                $entityManager->persist($isAffected);
            }
            $entityManager->persist($signalement);
            $entityManager->persist($suivi);
            $entityManager->flush();
            $this->addFlash('success', 'Signalement cloturé avec succès !');
            return $this->redirectToRoute('back_index');
        }
        $criticitesArranged = [];
        foreach ($signalement->getCriticites() as $criticite) {
            $criticitesArranged[$criticite->getCritere()->getSituation()->getLabel()][$criticite->getCritere()->getLabel()] = $criticite;
        }

        return $this->render('back/signalement/view.html.twig', [
            'title' => 'Signalement',
            'situations' => $criticitesArranged,
            'affectations' => $signalement->getAffectations(),
            'needValidation' => $signalement->getStatut() === Signalement::STATUS_NEED_VALIDATION,
            'isAffected' => $isAffected,
            'isAccepted' => $isAccepted,
            'isClosed' => $signalement->getStatut() === Signalement::STATUS_CLOSED,
            'isClosedForMe' => $isClosedForMe,
            'isRefused' => $isRefused,
            'signalement' => $signalement,
            'partners' => $partnerRepository->findAllOrByInseeIfCommune($signalement->getInseeOccupant(),$signalement->getTerritory()),
            'clotureForm' => $clotureForm->createView(),
            'tags' => $tagsRepository->findAllActive($signalement->getTerritory())
        ]);
    }

    #[Route('/{uuid}/edit', name: 'back_signalement_edit', methods: ['GET', 'POST'])]
    public function editSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine, SituationRepository $situationRepository, HttpClientInterface $httpClient): Response
    {
        $title = 'Administration - Edition signalement #' . $signalement->getReference();
        $etats = ["Etat moyen", "Mauvais état", "Très mauvais état"];
        $etats_classes = ["moyen", "grave", "tres-grave"];
        $form = $this->createForm(SignalementType::class, $signalement);
        $form->handleRequest($request);
        if ($form->isSubmitted() /*&& $form->isValid()*/) {
            //TODO INSEE AP
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
            $suivi->setDescription('Modification du signalement par un partner');
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
        } else if ($form->isSubmitted()) {
//            dd($form->getErrors()[0]);
            return $this->json(['response' => $form->getErrors()]);
        }


        return $this->render('back/signalement/edit.html.twig', [
            'title' => $title,
            'form' => $form->createView(),
            'signalement' => $signalement,
            'situations' => $situationRepository->findAllActive(),
            'etats' => $etats,
            'etats_classes' => $etats_classes
        ]);
    }

    #[Route('/{uuid}/delete', name: 'back_signalement_delete', methods: "POST")]
    public function deleteSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('SIGN_DELETE', $signalement);
        if ($this->isCsrfTokenValid('signalement_delete_' . $signalement->getId(), $request->get('_token'))) {
            $signalement->setStatut(Signalement::STATUS_ARCHIVED);
            $doctrine->getManager()->persist($signalement);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Signalement supprimé avec succès !');
        } else
            $this->addFlash('error', 'Une erreur est survenu lors de la suppression');
        return $this->redirectToRoute('back_index');
    }

}