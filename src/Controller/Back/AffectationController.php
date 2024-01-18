<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Event\AffectationAnsweredEvent;
use App\Factory\Interconnection\Oilhi\DossierMessageFactory;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Messenger\InterconnectionBus;
use App\Repository\AffectationRepository;
use App\Repository\PartnerRepository;
use App\Repository\SuiviRepository;
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
        private InterconnectionBus $interconnectionBus,
        private EventDispatcherInterface $eventDispatcher,
        private DossierMessageFactory $dossierMessageFactory
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
                        $this->dispatchDossier($affectation);
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

    #[Route('/{uuid}/affectation/remove', name: 'back_signalement_remove_partner')]
    public function removePartnerAffectation(
        Request $request,
        Signalement $signalement,
        AffectationRepository $affectationRepository,
    ): RedirectResponse|JsonResponse {
        $this->denyAccessUnlessGranted('ASSIGN_TOGGLE', $signalement);
        if ($this->isCsrfTokenValid('signalement_remove_partner_'.$signalement->getId(), $request->get('_token'))) {
            $idAffectation = $request->get('affectation');
            if (!empty($idAffectation)) {
                $affectation = $affectationRepository->findOneBy(['id' => $idAffectation]);
                $partnersIdToRemove = [];
                $partnersIdToRemove[] = $affectation->getPartner()->getId();
                $this->affectationManager->removeAffectationsFrom($signalement, [], $partnersIdToRemove);
            }
            $this->affectationManager->flush();
            $this->addFlash('success', 'Le partenaire a été désaffecté.');

            return $this->json(['status' => 'success']);
        }

        return $this->json(['status' => 'denied'], 400);
    }

    #[Route('/{signalement}/{affectation}/{user}/response', name: 'back_signalement_affectation_response', methods: 'POST')]
    public function affectationResponseSignalement(
        SuiviManager $suiviManager,
        UserManager $userManager,
        ParameterBagInterface $parameterBag,
        Signalement $signalement,
        Affectation $affectation,
        User $user,
        Request $request,
        SuiviRepository $suiviRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ASSIGN_ANSWER', $affectation);
        if ($this->isCsrfTokenValid('signalement_affectation_response_'.$signalement->getId(), $request->get('_token'))
            && $response = $request->get('signalement-affectation-response')
        ) {
            $status = isset($response['accept']) ? Affectation::STATUS_ACCEPTED : Affectation::STATUS_REFUSED;
            $motifRefus = (Affectation::STATUS_REFUSED === $status) ? $response['motifRefus'] : null;
            $affectation = $this->affectationManager->updateAffectation($affectation, $user, $status, $motifRefus);

            $suiviAffectationAccepted = $suiviRepository->findSuiviByDescription(
                $signalement,
                '<p>Suite à votre signalement, le ou les partenaires compétents'
            );
            $affectationAccepted = $signalement->getAffectations()->filter(function (Affectation $affectation) {
                return Affectation::STATUS_ACCEPTED === $affectation->getStatut();
            });

            if (!$signalement->getIsImported()
                && 1 === $affectationAccepted->count()
                && Affectation::STATUS_ACCEPTED === $affectation->getStatut()
                && empty($suiviAffectationAccepted)
            ) {
                $adminEmail = $parameterBag->get('user_system_email');
                $adminUser = $userManager->findOneBy(['email' => $adminEmail]);
                $suiviManager->createSuivi(
                    user: $adminUser,
                    signalement: $signalement,
                    params: [
                        'description' => $parameterBag->get('suivi_message')['first_accepted_affectation'],
                        'type' => Suivi::TYPE_AUTO,
                    ],
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

    private function dispatchDossier(Affectation $affectation): void
    {
        $partner = $affectation->getPartner();
        if ($partner->canSyncWithEsabora() || $partner->canSyncWithOilhi()) {
            $affectation->setIsSynchronized(true);
            $this->affectationManager->save($affectation);
            $this->interconnectionBus->dispatch($affectation);
        }
    }
}
