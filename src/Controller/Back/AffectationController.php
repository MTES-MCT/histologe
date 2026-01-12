<?php

namespace App\Controller\Back;

use App\Dto\AgentSelection;
use App\Dto\RefusAffectation;
use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Form\AgentSelectionType;
use App\Form\RefusAffectationType;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\UserSignalementSubscriptionManager;
use App\Repository\AffectationRepository;
use App\Repository\PartnerRepository;
use App\Security\Voter\AffectationVoter;
use App\Security\Voter\SignalementVoter;
use App\Service\EmailAlertChecker;
use App\Service\FormHelper;
use App\Service\MessageHelper;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/bo/signalements')]
class AffectationController extends AbstractController
{
    public function __construct(
        private readonly SignalementManager $signalementManager,
        private readonly AffectationManager $affectationManager,
        private readonly PartnerRepository $partnerRepository,
        private readonly EmailAlertChecker $emailAlertChecker,
    ) {
    }

    private function getHtmlTargetContentsForAffectationWithActionItems(Signalement $signalement): array
    {
        return [
            [
                'target' => '#affectations-with-action',
                'content' => $this->renderView('back/signalement/view/affectation/_item-with-action.html.twig', [
                    'signalement' => $signalement,
                    'partnerEmailAlerts' => $this->emailAlertChecker->buildPartnerEmailAlert($signalement),
                ]
                ),
            ],
        ];
    }

    /**
     * @throws ExceptionInterface
     * @throws InvalidArgumentException
     */
    #[Route('/{uuid:signalement}/affectation/toggle', name: 'back_signalement_toggle_affectation')]
    #[IsGranted(SignalementVoter::SIGN_AFFECTATION_TOGGLE, subject: 'signalement')]
    public function toggleAffectationSignalement(
        Request $request,
        Signalement $signalement,
        TagAwareCacheInterface $cache,
    ): RedirectResponse|JsonResponse {
        if ($this->isCsrfTokenValid('signalement_affectation_'.$signalement->getId(), (string) $request->request->get('_token'))) {
            $unnotifiedPartners = [];
            $requestData = $request->request->all();
            $data = $requestData['signalement-affectation'] ?? null;
            if (isset($data['partners'])) {
                /** @var User $user */
                $user = $this->getUser();
                $postedPartner = $data['partners'];
                $filterInjonctionBailleur = (SignalementStatus::INJONCTION_BAILLEUR === $signalement->getStatut());
                $affectablePartners = $this->signalementManager->findAffectablePartners($signalement, $filterInjonctionBailleur);
                $alreadyAffectedPartner = $affectablePartners['affected'];
                $alreadyAffectedPartnersIds = array_map(fn (array $partner) => $partner['id'], $alreadyAffectedPartner);
                $partnersIdToAdd = array_diff($postedPartner, $alreadyAffectedPartnersIds);
                $partnersIdToRemove = array_diff($alreadyAffectedPartnersIds, $postedPartner);

                foreach ($partnersIdToAdd as $partnerIdToAdd) {
                    $canAffectPartner = false;
                    foreach ($affectablePartners['not_affected'] as $affectablePartner) {
                        if ($affectablePartner['id'] == $partnerIdToAdd) {
                            $canAffectPartner = true;
                            break;
                        }
                    }
                    if (!$canAffectPartner) {
                        continue;
                    }
                    $partner = $this->partnerRepository->find($partnerIdToAdd);
                    if (!$partner) {
                        continue;
                    }
                    $affectation = $this->affectationManager->createAffectationFrom(
                        $signalement,
                        $partner,
                        $user
                    );
                    if ($affectation instanceof Affectation) {
                        $signalement->addAffectation($affectation);
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
            $flashMessage = ['type' => 'success', 'title' => 'Affectations enregistrées', 'message' => $successMessage];
            $htmlTargetContents = $this->getHtmlTargetContentsForAffectationWithActionItems($signalement);

            return $this->json(['stayOnPage' => true, 'flashMessages' => [$flashMessage], 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
        }
        $flashMessage = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

        return $this->json(['stayOnPage' => true, 'flashMessages' => [$flashMessage]]);
    }

    #[Route('/{uuid:signalement}/affectation/remove', name: 'back_signalement_remove_partner')]
    #[IsGranted(SignalementVoter::SIGN_AFFECTATION_TOGGLE, subject: 'signalement')]
    public function removePartnerAffectation(
        Request $request,
        Signalement $signalement,
        AffectationRepository $affectationRepository,
    ): RedirectResponse|JsonResponse {
        $idAffectation = $request->query->get('affectation');
        $affectation = $affectationRepository->findOneBy(['id' => $idAffectation]);
        if (!$affectation || $affectation->getSignalement()->getId() !== $signalement->getId()) {
            $flashMessage = ['type' => 'alert', 'title' => 'Erreur', 'message' => 'Affectation introuvable.'];

            return $this->json(['stayOnPage' => true, 'flashMessages' => [$flashMessage]]);
        }
        if ($this->isCsrfTokenValid('signalement_remove_partner_'.$signalement->getId(), (string) $request->request->get('_token'))) {
            $partnersIdToRemove = [];
            $partnersIdToRemove[] = $affectation->getPartner()->getId();
            $this->affectationManager->removeAffectationsFrom($signalement, [], $partnersIdToRemove);
            $this->affectationManager->flush();
            $flashMessage = ['type' => 'success', 'title' => 'Affectation supprimée', 'message' => 'L\'affectation du partenaire '.$affectation->getPartner()->getNom().' a bien été supprimée.'];
            $htmlTargetContents = $this->getHtmlTargetContentsForAffectationWithActionItems($signalement);

            return $this->json(['stayOnPage' => true, 'flashMessages' => [$flashMessage], 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
        }

        $flashMessage = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

        return $this->json(['stayOnPage' => true, 'flashMessages' => [$flashMessage]]);
    }

    #[Route('/affectation/{affectation}/reinit', name: 'back_signalement_affectation_reinit', methods: ['POST'])]
    public function reinitAffectation(
        Affectation $affectation,
        Request $request,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(AffectationVoter::AFFECTATION_REINIT, $affectation);
        $message = 'Une erreur est survenue lors de la réinitialisation de l\'affectation.';
        if ($this->isCsrfTokenValid('reinit_affectation_'.$affectation->getSignalement()->getUuid(), (string) $request->request->get('_token'))) {
            /** @var User $user */
            $user = $this->getUser();
            $this->affectationManager->removeAffectationAndSubscriptions($affectation);
            $this->affectationManager->createAffectation($affectation->getSignalement(), $affectation->getPartner(), $user);
            $this->affectationManager->flush();
            $flashMessage = ['type' => 'success', 'title' => 'Affectation réinitialisée', 'message' => 'L\'affectation du partenaire '.$affectation->getPartner()->getNom().' a bien été réinitialisée.'];
            $htmlTargetContents = $this->getHtmlTargetContentsForAffectationWithActionItems($affectation->getSignalement());

            return $this->json(['stayOnPage' => true, 'flashMessages' => [$flashMessage], 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
        }
        $message = MessageHelper::ERROR_MESSAGE_CSRF;

        $flashMessage = ['type' => 'alert', 'title' => 'Erreur', 'message' => $message];

        return $this->json(['stayOnPage' => true, 'flashMessages' => [$flashMessage]]);
    }

    #[Route('/affectation/{affectation}/accept', name: 'back_signalement_affectation_accept', methods: 'POST')]
    #[IsGranted(AffectationVoter::AFFECTATION_ACCEPT_OR_REFUSE, subject: 'affectation')]
    public function affectationResponseSignalement(
        Affectation $affectation,
        Request $request,
        UserSignalementSubscriptionManager $userSignalementSubscriptionManager,
    ): Response {
        $signalement = $affectation->getSignalement();
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->isAloneInPartner($user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory()))) {
            $agentsSelection = (new AgentSelection())->setSignalement($signalement);
            $agentsSelectionFormRoute = $this->generateUrl('back_signalement_affectation_accept', ['affectation' => $affectation->getId()]);
            $form = $this->createForm(
                AgentSelectionType::class,
                $agentsSelection,
                ['action' => $agentsSelectionFormRoute]
            );
            $form->handleRequest($request);

            if (!$form->isSubmitted()) {
                return $this->json(['code' => Response::HTTP_BAD_REQUEST]);
            }
            if (!$form->isValid()) {
                $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $form, withPrefix: true)];

                return $this->json($response, $response['code']);
            }
            foreach ($agentsSelection->getAgents() as $agent) {
                $userSignalementSubscriptionManager->createOrGet($agent, $signalement, $user, $affectation);
                $userSignalementSubscriptionManager->flush();
            }
            $this->affectationManager->updateAffectation(
                affectation: $affectation,
                user: $user,
                status: AffectationStatus::ACCEPTED,
                partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory())
            );
            $this->addFlash('success', ['title' => 'Affectation acceptée', 'message' => 'L\'affectation a bien été acceptée.']);

            $url = $this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);

            return $this->json(['redirect' => true, 'url' => $url]);
        }
        if ($this->isCsrfTokenValid('signalement_affectation_response_'.$signalement->getId(), (string) $request->request->get('_token'))) {
            $this->affectationManager->updateAffectation(
                affectation: $affectation,
                user: $user,
                status : AffectationStatus::ACCEPTED,
                partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory())
            );
            $userSignalementSubscriptionManager->createOrGet($user, $signalement, $user, $affectation);
            $userSignalementSubscriptionManager->flush();

            $this->addFlash('success', ['title' => 'Affectation acceptée', 'message' => 'L\'affectation a bien été acceptée.']);
        } else {
            $this->addFlash('error', ['title' => 'Erreur', 'message' => 'L\'affectation n\'a pas pu être acceptée, veuillez réessayer.']);
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/affectation/{affectation}/deny', name: 'back_signalement_affectation_deny', methods: 'POST')]
    #[IsGranted(AffectationVoter::AFFECTATION_ACCEPT_OR_REFUSE, subject: 'affectation')]
    public function affectationResponseDenySignalement(
        Affectation $affectation,
        Request $request,
    ): JsonResponse {
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
            partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory()),
            motifRefus: $refusAffectation->getMotifRefus(),
            message: $refusAffectation->getDescription(),
            files: $refusAffectation->getFiles()
        );
        $this->addFlash('success', ['title' => 'Affectation refusée', 'message' => 'L\'affectation a bien été refusée.']);

        $url = $this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(['redirect' => true, 'url' => $url]);
    }
}
