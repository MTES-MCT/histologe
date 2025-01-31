<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\User;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Messenger\InterconnectionBus;
use App\Repository\AffectationRepository;
use App\Repository\PartnerRepository;
use App\Security\Voter\AffectationVoter;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/bo/signalements')]
class AffectationController extends AbstractController
{
    public function __construct(
        private readonly SignalementManager $signalementManager,
        private readonly AffectationManager $affectationManager,
        private readonly PartnerRepository $partnerRepository,
        private readonly InterconnectionBus $interconnectionBus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     * @throws InvalidArgumentException
     */
    #[Route('/{uuid:signalement}/affectation/toggle', name: 'back_signalement_toggle_affectation')]
    public function toggleAffectationSignalement(
        Request $request,
        Signalement $signalement,
        TagAwareCacheInterface $cache,
    ): RedirectResponse|JsonResponse {
        $this->denyAccessUnlessGranted(AffectationVoter::TOGGLE, $signalement);
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
                    $partner = $this->partnerRepository->findOneBy(['id' => $partnerIdToAdd, 'territory' => $signalement->getTerritory(), 'isArchive' => false]);
                    if (!$partner) {
                        continue;
                    }
                    $affectation = $this->affectationManager->createAffectationFrom(
                        $signalement,
                        $partner,
                        $user
                    );
                    if ($affectation instanceof Affectation) {
                        $this->affectationManager->persist($affectation);
                        $this->interconnectionBus->dispatch($affectation);
                    }
                }
                $this->affectationManager->removeAffectationsFrom($signalement, $postedPartner, $partnersIdToRemove);
                $cache->invalidateTags([SearchFilterOptionDataProvider::CACHE_TAG, SearchFilterOptionDataProvider::CACHE_TAG.$signalement->getTerritory()->getZip()]);
            } else {
                $this->affectationManager->removeAffectationsFrom($signalement);
            }
            $this->affectationManager->flush();
            $this->addFlash('success', 'Les affectations ont bien été effectuées.');

            return $this->json(['status' => 'success']);
        }

        return $this->json(['status' => 'denied'], 400);
    }

    #[Route('/{uuid:signalement}/affectation/remove', name: 'back_signalement_remove_partner')]
    public function removePartnerAffectation(
        Request $request,
        Signalement $signalement,
        AffectationRepository $affectationRepository,
    ): RedirectResponse|JsonResponse {
        $this->denyAccessUnlessGranted(AffectationVoter::TOGGLE, $signalement);
        $idAffectation = $request->get('affectation');
        $affectation = $affectationRepository->findOneBy(['id' => $idAffectation]);
        if (!$affectation || $affectation->getSignalement()->getId() !== $signalement->getId()) {
            return $this->json(['status' => 'denied'], 403);
        }
        if ($this->isCsrfTokenValid('signalement_remove_partner_'.$signalement->getId(), $request->get('_token'))) {
            $partnersIdToRemove = [];
            $partnersIdToRemove[] = $affectation->getPartner()->getId();
            $this->affectationManager->removeAffectationsFrom($signalement, [], $partnersIdToRemove);
            $this->affectationManager->flush();
            $this->addFlash('success', 'Le partenaire a été désaffecté.');

            return $this->json(['status' => 'success']);
        }

        return $this->json(['status' => 'denied'], 400);
    }

    #[Route(
        '/{signalement}/{affectation}/{user}/response',
        name: 'back_signalement_affectation_response',
        methods: 'POST'
    )]
    public function affectationResponseSignalement(
        Signalement $signalement,
        Affectation $affectation,
        User $user,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(AffectationVoter::ANSWER, $affectation);
        if ($this->isCsrfTokenValid('signalement_affectation_response_'.$signalement->getId(), $request->get('_token'))
            && $response = $request->get('signalement-affectation-response')
        ) {
            $status = isset($response['accept']) ? Affectation::STATUS_ACCEPTED : Affectation::STATUS_REFUSED;
            $motifRefus = (Affectation::STATUS_REFUSED === $status) ? $response['motifRefus'] : null;
            $message = $response['suivi'] ?? null;
            $this->affectationManager->updateAffectation($affectation, $user, $status, $motifRefus, $message);
            $this->addFlash('success', 'Affectation mise à jour avec succès !');
        } else {
            $this->addFlash('error', "Une erreur est survenu lors de l'affectation");
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }
}
