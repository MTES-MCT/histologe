<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\AffectationAnsweredEvent;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Messenger\EsaboraBus;
use App\Repository\PartnerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/signalements')]
class AffectationController extends AbstractController
{
    public function __construct(
        private SignalementManager $signalementManager,
        private AffectationManager $affectationManager,
        private PartnerRepository $partnerRepository,
        private EsaboraBus $esaboraBus,
        private EventDispatcherInterface $eventDispatcher,
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
                /** @var User $user */
                $user = $this->getUser();
                $postedPartner = $data['partners'];
                $alreadyAffectedPartner = $this->signalementManager->findPartners($signalement);
                $partnersIdToAdd = array_diff($postedPartner, $alreadyAffectedPartner);
                $partnersIdToRemove = array_diff($alreadyAffectedPartner, $postedPartner);

                foreach ($partnersIdToAdd as $partnerIdToAdd) {
                    $partner = $this->partnerRepository->find($partnerIdToAdd);
                    $affectation = $this->affectationManager->createAffectationFrom(
                        $signalement,
                        $partner,
                        $user
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
        SuiviManager $suiviManager,
        ParameterBagInterface $parameterBag,
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
            $affectationAccepted = $signalement->getAffectations()->filter(function (Affectation $affectation) {
                return Affectation::STATUS_ACCEPTED === $affectation->getStatut();
            });

            if (1 === $affectationAccepted->count()
                && Affectation::STATUS_ACCEPTED === $affectation->getStatut()
            ) {
                $suiviManager->createSuivi(
                    user: $user,
                    signalement: $signalement,
                    params: ['description' => $parameterBag->get('suivi_message')['first_affectation']],
                    isPublic: true,
                    flush: true
                );
            }

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
        if ($partner->getEsaboraToken() && $partner->getEsaboraUrl() && $partner->isEsaboraActive()) {
            $this->esaboraBus->dispatch($affectation);
        }
    }
}
