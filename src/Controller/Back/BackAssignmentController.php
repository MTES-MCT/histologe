<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\JobEvent;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\AffectationAnsweredEvent;
use App\Factory\DossierMessageFactory;
use App\Manager\AffectationManager;
use App\Manager\JobEventManager;
use App\Manager\SignalementManager;
use App\Repository\PartnerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/bo/s')]
class BackAssignmentController extends AbstractController
{
    public function __construct(
        private SignalementManager $signalementManager,
        private AffectationManager $affectationManager,
        private PartnerRepository $partnerRepository,
        private DossierMessageFactory $dossierMessageFactory,
        private MessageBusInterface $messageBus,
        private EventDispatcherInterface $eventDispatcher,
        private JobEventManager $jobEventManager,
        private SerializerInterface $serializer
    ) {
    }

    #[Route('/{uuid}/affectation/toggle', name: 'back_signalement_toggle_affectation')]
    public function toggleAffectationSignalement(
        Request $request,
        Signalement $signalement,
    ): RedirectResponse|JsonResponse {
        $this->denyAccessUnlessGranted('ASSIGN_TOGGLE', $signalement);
        if ($this->isCsrfTokenValid('signalement_affectation_'.$signalement->getId(), $request->get('_token'))) {
            $data = $request->get('signalement-affectation');
            if (isset($data['partners'])) {
                $postedPartner = $data['partners'];
                $alreadyAffectedPartner = $this->signalementManager->findPartners($signalement);
                $partnersIdToAdd = array_diff($postedPartner, $alreadyAffectedPartner);
                $partnersIdToRemove = array_diff($alreadyAffectedPartner, $postedPartner);

                foreach ($partnersIdToAdd as $partnerIdToAdd) {
                    $partner = $this->partnerRepository->find($partnerIdToAdd);
                    $affectation = $this->affectationManager->createAffectationFrom(
                        $signalement,
                        $partner,
                        $this->getUser()
                    );
                    if ($affectation instanceof Affectation) {
                        $this->affectationManager->persist($affectation);
                        $this->dispatchDossierEsabora($affectation);
                    }
                }
                $this->affectationManager->removeAffectationsFrom($signalement, $postedPartner, $partnersIdToRemove);
            } else {
                $this->affectationManager->removeAffectationsFrom($signalement);
            }
            $this->affectationManager->flush();
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
    ): Response {
        $this->denyAccessUnlessGranted('ASSIGN_ANSWER', $affectation);
        if ($this->isCsrfTokenValid('signalement_affectation_response_'.$signalement->getId(), $request->get('_token'))
            && $response = $request->get('signalement-affectation-response')
        ) {
            $status = isset($response['accept']) ? Affectation::STATUS_ACCEPTED : Affectation::STATUS_REFUSED;
            $affectation = $this->affectationManager->updateAffectation($affectation, $user, $status);
            $this->addFlash('success', 'Affectation mise à jour avec succès !');
            if (Affectation::STATUS_REFUSED == $status) {
                $this->dispatchAffectationAnsweredEvent($affectation, $response);
            }
        } else {
            $this->addFlash('error', "Une erreur est survenu lors de l'affectation");
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    private function dispatchAffectationAnsweredEvent(
        Affectation $affectation,
        array $response
    ): void {
        if (isset($response['suivi'])) {
            $this->eventDispatcher->dispatch(
                new AffectationAnsweredEvent($affectation, $this->getUser(), $response),
                AffectationAnsweredEvent::NAME
            );
        }
    }

    private function dispatchDossierEsabora(Affectation $affectation): void
    {
        $partner = $affectation->getPartner();
        if ($partner->getEsaboraToken() && $partner->getEsaboraUrl()) {
            $dossierMessage = $this->dossierMessageFactory->createInstance($affectation);
            try {
                $this->messageBus->dispatch($dossierMessage);
            } catch (\Throwable $exception) {
                $this->jobEventManager->createJobEvent(
                    type: 'esabora',
                    title: 'push_dossier',
                    message: json_encode($dossierMessage->preparePayload(), \JSON_THROW_ON_ERROR),
                    response: $exception->getMessage(),
                    status: JobEvent::STATUS_FAILED,
                    signalementId: $affectation->getSignalement()->getId(),
                    partnerId: $affectation->getPartner()->getId()
                );
            }
        }
    }
}
