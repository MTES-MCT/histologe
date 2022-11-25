<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\AffectationAnsweredEvent;
use App\Manager\AffectationManager;
use App\Repository\PartnerRepository;
use App\Service\EsaboraService;
use App\Service\NotificationService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/s')]
class BackAssignmentController extends AbstractController
{
    #[Route('/{uuid}/affectation/toggle', name: 'back_signalement_toggle_affectation')]
    public function toggleAffectationSignalement(Signalement $signalement, EsaboraService $esaboraService, ManagerRegistry $doctrine, Request $request, PartnerRepository $partnerRepository, NotificationService $notificationService): RedirectResponse|JsonResponse
    {
        $this->denyAccessUnlessGranted('ASSIGN_TOGGLE', $signalement);
        if ($this->isCsrfTokenValid('signalement_affectation_'.$signalement->getId(), $request->get('_token'))) {
            $data = $request->get('signalement-affectation');
            if (isset($data['partners'])) {
                $postedPartner = $data['partners'];
                $alreadyAffectedPartner = $signalement->getAffectations()->map(function (Affectation $affectation) {
                    return $affectation->getPartner()->getId();
                })->toArray();
                $partnersToAdd = array_diff($postedPartner, $alreadyAffectedPartner);
                $partnersToRemove = array_diff($alreadyAffectedPartner, $postedPartner);
                foreach ($partnersToAdd as $partnerIdToAdd) {
                    $partner = $partnerRepository->find($partnerIdToAdd);
                    $affectation = new Affectation();
                    $affectation->setSignalement($signalement);
                    $affectation->setPartner($partner);
                    $affectation->setAffectedBy($this->getUser());
                    $affectation->setTerritory($partner->getTerritory());
                    $doctrine->getManager()->persist($affectation);
                    if ($partner->getEsaboraToken() && $partner->getEsaboraUrl()) {
                        $esaboraService->post($affectation);
                    }
                }
                foreach ($partnersToRemove as $partnerIdToRemove) {
                    $partner = $partnerRepository->find($partnerIdToRemove);
                    $signalement->getAffectations()->filter(function (Affectation $affectation) use ($doctrine, $partner) {
                        if ($affectation->getPartner()->getId() === $partner->getId()) {
                            $doctrine->getManager()->remove($affectation);
                        }
                    });
                }
            } else {
                $signalement->getAffectations()->filter(function (Affectation $affectation) use ($doctrine) {
                    $doctrine->getManager()->remove($affectation);
                });
            }

            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Les affectations ont bien été effectuées.');

            return $this->json(['status' => 'success']);
        }

        return $this->json(['status' => 'denied'], 400);
    }

    #[Route('/{signalement}/{affectation}/{user}/response', name: 'back_signalement_affectation_response', methods: 'POST')]
    public function affectationResponseSignalement(
        Signalement $signalement,
        Affectation $affectation,
        User $user,
        Request $request,
        AffectationManager $affectationManager,
        EventDispatcherInterface $eventDispatcher,
    ): Response {
        $this->denyAccessUnlessGranted('ASSIGN_ANSWER', $affectation);
        if ($this->isCsrfTokenValid('signalement_affectation_response_'.$signalement->getId(), $request->get('_token'))
            && $response = $request->get('signalement-affectation-response')
        ) {
            $status = isset($response['accept']) ? Affectation::STATUS_ACCEPTED : Affectation::STATUS_REFUSED;
            $affectation = $affectationManager->updateAffection($affectation, $status);
            $this->addFlash('success', 'Affectation mise à jour avec succès !');
            $this->dispatchAffectationAnsweredEvent($eventDispatcher, $affectation, $response);
        } else {
            $this->addFlash('error', "Une erreur est survenu lors de l'affectation");
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    private function dispatchAffectationAnsweredEvent(
        EventDispatcherInterface $eventDispatcher,
        Affectation $affectation,
        array $response
    ): void {
        if (isset($response['suivi'])) {
            $eventDispatcher->dispatch(
                new AffectationAnsweredEvent($affectation, $response),
                AffectationAnsweredEvent::NAME
            );
        }
    }
}
