<?php

namespace App\Controller\Back;

use App\Dto\RefusAffectation;
use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Form\AcceptAffectationType;
use App\Form\RefusAffectationType;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\UserSignalementSubscriptionManager;
use App\Repository\AffectationRepository;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use App\Security\Voter\AffectationVoter;
use App\Service\FormHelper;
use App\Service\NotificationAndMailSender;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use App\Service\Signalement\SignalementAffectationHelper;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/bo/signalements')]
class AffectationController extends AbstractController
{
    public function __construct(
        private readonly SignalementManager $signalementManager,
        private readonly AffectationManager $affectationManager,
        private readonly PartnerRepository $partnerRepository,
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
            $unnotifiedPartners = [];
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
                        if (!$partner->receiveEmailNotifications()) {
                            $unnotifiedPartners[] = $partner;
                        }
                    }
                }
                $this->affectationManager->removeAffectationsFrom($signalement, $postedPartner, $partnersIdToRemove);
                $cache->invalidateTags([SearchFilterOptionDataProvider::CACHE_TAG, SearchFilterOptionDataProvider::CACHE_TAG.$signalement->getTerritory()->getZip()]);
            } else {
                $this->affectationManager->removeAffectationsFrom($signalement);
            }
            $this->affectationManager->flush();
            $successMessage = 'Les affectations ont bien été effectuées.';
            if (!empty($unnotifiedPartners)) {
                $successMessage .= '<br>Attention, certains partenaires affectés ont désactivé les notifications par e-mail : ';
                $successMessage .= implode(', ', array_map(fn ($partner) => $partner->getNom(), $unnotifiedPartners));
            }
            $this->addFlash('success success-raw', $successMessage);

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

    #[Route('/affectation/{affectation}/reinit', name: 'back_signalement_affectation_reinit', methods: ['POST'])]
    public function reinitAffectation(
        Affectation $affectation,
        Request $request,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(AffectationVoter::AFFECTATION_REINIT, $affectation);
        if ($this->isCsrfTokenValid('reinit_affectation_'.$affectation->getSignalement()->getUuid(), $request->get('_token'))) {
            /** @var User $user */
            $user = $this->getUser();
            $this->affectationManager->removeAffectationAndSubscription($affectation);
            $this->affectationManager->createAffectation(
                $affectation->getSignalement(),
                $affectation->getPartner(),
                $user,
            );
            $this->affectationManager->flush();
        } else {
            $this->addFlash('error', 'Token CSRF invalide, merci de réessayer.');
        }

        return new RedirectResponse($this->generateUrl('back_signalement_view', ['uuid' => $affectation->getSignalement()->getUuid()]));
    }

    #[Route('/affectation/{affectation}/accept', name: 'back_signalement_affectation_accept', methods: 'POST')]
    public function affectationResponseSignalement(
        Affectation $affectation,
        Request $request,
        UserRepository $userRepository,
        UserSignalementSubscriptionManager $userSignalementSubscriptionManager,
        NotificationAndMailSender $notificationAndMailSender,
        #[Autowire(env: 'FEATURE_NEW_DASHBOARD')] ?int $featureNewDashboard = null,
    ): Response {
        $this->denyAccessUnlessGranted(AffectationVoter::ANSWER, $affectation);
        $signalement = $affectation->getSignalement();
        /** @var User $user */
        $user = $this->getUser();
        if ($featureNewDashboard) {
            $partnerAgents = SignalementAffectationHelper::getAffectationUsers($user, $signalement);
            $acceptAffectationFormRoute = $this->generateUrl('back_signalement_affectation_accept', ['affectation' => $affectation->getId()]);
            $form = $this->createForm(AcceptAffectationType::class, null, ['action' => $acceptAffectationFormRoute, 'agents' => $partnerAgents]);
            $form->handleRequest($request);

            if (!$form->isSubmitted()) {
                return $this->json(['code' => Response::HTTP_BAD_REQUEST]);
            }
            if (!$form->isValid()) {
                $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $form, withPrefix: true)];

                return $this->json($response, $response['code']);
            }
            foreach ($form->getData()['agents'] as $userId) {
                $userToSubscribe = $userRepository->find((int) $userId);
                $subscription = $userSignalementSubscriptionManager->createOrGet(
                    $userToSubscribe,
                    $signalement,
                    $user
                );
                $userSignalementSubscriptionManager->flush();
                $description = $user->getNomComplet().' vous a attribué le dossier #'.$signalement->getReference().'. Vous recevrez les mises à jour pour ce dossier.';
                $notificationAndMailSender->sendNewSubscription($subscription, $description);
            }
            $this->affectationManager->updateAffectation(
                affectation: $affectation,
                user: $user,
                status: AffectationStatus::ACCEPTED
            );
            $this->addFlash('success', 'Affectation acceptée avec succès !');

            $url = $this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);

            return $this->json(['redirect' => true, 'url' => $url]);
        }
        if ($this->isCsrfTokenValid('signalement_affectation_response_'.$signalement->getId(), $request->get('_token'))) {
            $this->affectationManager->updateAffectation($affectation, $user, AffectationStatus::ACCEPTED);
            $this->addFlash('success', 'Affectation acceptée avec succès !');
        } else {
            $this->addFlash('error', "Une erreur est survenue lors de l'affectation");
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/affectation/{affectation}/deny', name: 'back_signalement_affectation_deny', methods: 'POST')]
    public function affectationResponseDenySignalement(
        Affectation $affectation,
        Request $request,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(AffectationVoter::ANSWER, $affectation);
        $signalement = $affectation->getSignalement();
        /** @var User $user */
        $user = $this->getUser();
        $refusAffectation = (new RefusAffectation())->setSignalement($signalement);
        $refusAffectationFormRoute = $this->generateUrl('back_signalement_affectation_deny', ['affectation' => $affectation->getId()]);
        $form = $this->createForm(RefusAffectationType::class, $refusAffectation, ['action' => $refusAffectationFormRoute]);
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            return $this->json(['code' => Response::HTTP_BAD_REQUEST]);
        }
        if (!$form->isValid()) {
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $form, withPrefix: true)];

            return $this->json($response, $response['code']);
        }
        $this->affectationManager->updateAffectation(
            affectation: $affectation,
            user: $user,
            status: AffectationStatus::REFUSED,
            motifRefus: $refusAffectation->getMotifRefus(),
            message: $refusAffectation->getDescription(),
            files: $refusAffectation->getFiles()
        );
        $this->addFlash('success', 'Affectation refusée avec succès !');

        $url = $this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(['redirect' => true, 'url' => $url]);
    }
}
