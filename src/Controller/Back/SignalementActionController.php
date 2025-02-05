<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Enum\MotifRefus;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Tag;
use App\Entity\User;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Repository\AffectationRepository;
use App\Repository\SuiviRepository;
use App\Service\BetaGouv\RnbService;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/signalements')]
class SignalementActionController extends AbstractController
{
    #[Route('/{uuid:signalement}/validation/response', name: 'back_signalement_validation_response', methods: 'GET')]
    public function validationResponseSignalement(
        Signalement $signalement,
        Request $request,
        NotificationMailerRegistry $notificationMailerRegistry,
        SuiviManager $suiviManager,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_VALIDATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_validation_response_'.$signalement->getId(), $request->get('_token'))
            && $response = $request->get('signalement-validation-response')) {
            if (isset($response['accept'])) {
                $statut = Signalement::STATUS_ACTIVE;
                $description = 'validé';
                $signalement->setValidatedAt(new \DateTimeImmutable());
                $toRecipients = $signalement->getMailUsagers();

                foreach ($toRecipients as $toRecipient) {
                    $notificationMailerRegistry->send(
                        new NotificationMail(
                            type: NotificationMailerType::TYPE_SIGNALEMENT_VALIDATION_TO_USAGER,
                            to: $toRecipient,
                            territory: $signalement->getTerritory(),
                            signalement: $signalement,
                        )
                    );
                }
            } else {
                $statut = Signalement::STATUS_REFUSED;
                $motifRefus = MotifRefus::tryFrom($response['motifRefus']);
                if (!$motifRefus || mb_strlen($response['suivi']) < 10) {
                    $this->addFlash('error', 'Champs incorrects ou manquants !');

                    return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
                }
                $signalement->setMotifRefus($motifRefus);
                $description = 'cloturé car non-valide avec le motif suivant : '.$motifRefus->label().'<br>Plus précisément :<br>'.$response['suivi'];

                $toRecipients = $signalement->getMailUsagers();
                $notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_SIGNALEMENT_REFUSAL_TO_USAGER,
                        to: $toRecipients,
                        territory: $signalement->getTerritory(),
                        signalement: $signalement,
                        motif: $response['suivi'],
                    )
                );
            }
            /** @var User $user */
            $user = $this->getUser();
            $signalement->setStatut($statut);

            $suiviManager->createSuivi(
                user : $user,
                signalement: $signalement,
                description: 'Signalement '.$description,
                type : Suivi::TYPE_AUTO,
                isPublic: true,
                sendMail: false
            );

            $this->addFlash('success', 'Statut du signalement mis à jour avec succès !');
        } else {
            $this->addFlash('error', 'Une erreur est survenue...');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid:signalement}/suivi/add', name: 'back_signalement_add_suivi', methods: 'POST')]
    public function addSuiviSignalement(
        Signalement $signalement,
        Request $request,
        SuiviManager $suiviManager,
        LoggerInterface $logger,
    ): Response {
        $this->denyAccessUnlessGranted('COMMENT_CREATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_add_suivi_'.$signalement->getId(), $request->get('_token'))
            && $form = $request->get('signalement-add-suivi')) {
            $content = $form['content'];
            $content = preg_replace('/<p[^>]*>/', '', $content); // Remove the start <p> or <p attr="">
            $content = str_replace('</p>', '<br />', $content); // Replace the end
            if (mb_strlen($content) < 10) {
                $this->addFlash('error', 'Le contenu du suivi doit faire au moins 10 caractères !');

                return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
            }
            try {
                /** @var User $user */
                $user = $this->getUser();
                $suiviManager->createSuivi(
                    user: $user,
                    signalement: $signalement,
                    description: $content,
                    type: Suivi::TYPE_PARTNER,
                    isPublic: !empty($form['notifyUsager']),
                );
            } catch (\Throwable $exception) {
                $this->addFlash('error', 'Une erreur est survenue lors de la publication.');
                $logger->error($exception->getMessage());

                return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
            }

            $this->addFlash('success', 'Suivi publié avec succès !');
        } else {
            $this->addFlash('error', 'Une erreur de jeton est survenue lors de la publication.');
        }

        return $this->redirect(
            $this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]).'#suivis'
        );
    }

    #[Route('/{uuid:signalement}/suivi/delete', name: 'back_signalement_delete_suivi', methods: 'POST')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteSuivi(
        Request $request,
        Signalement $signalement,
        SuiviRepository $suiviRepository,
        ManagerRegistry $doctrine,
    ): RedirectResponse {
        if ($this->isCsrfTokenValid('signalement_delete_suivi_'.$signalement->getId(), $request->get('_token'))
            && $idSuivi = $request->get('suivi')
        ) {
            $suivi = $suiviRepository->findOneBy(['id' => $idSuivi]);
            if ($suivi) {
                /** @var User $user */
                $user = $this->getUser();
                $suivi->setDeletedAt(new \DateTimeImmutable());
                $suivi->setDeletedBy($user);
                $doctrine->getManager()->persist($suivi);
                $doctrine->getManager()->flush();

                $this->addFlash('success', 'Le suivi a été supprimé.');
            } else {
                $this->addFlash('success', 'Ce suivi n\'existe pas.');
            }
        }

        return $this->redirect(
            $this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]).'#suivis'
        );
    }

    #[Route('/{uuid:signalement}/reopen', name: 'back_signalement_reopen')]
    public function reopenSignalement(
        Signalement $signalement,
        Request $request,
        AffectationRepository $affectationRepository,
        SuiviManager $suiviManager,
    ): RedirectResponse|JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if ($this->isCsrfTokenValid('signalement_reopen_'.$signalement->getId(), $request->get('_token')) && $response = $request->get('signalement-action')) {
            if ($this->isGranted('ROLE_ADMIN_TERRITORY') && isset($response['reopenAll'])) {
                $affectationRepository->updateStatusBySignalement(Affectation::STATUS_WAIT, $signalement);
                $reopenFor = 'tous les partenaires';
            } elseif (!$this->isGranted('ROLE_ADMIN_TERRITORY') && Signalement::STATUS_CLOSED === $signalement->getStatut()) {
                $this->addFlash('error', 'Seul un responsable de territoire peut réouvrir un signalement clôturé !');

                return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
            } else {
                $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
                $reopenFor = mb_strtoupper($partner->getNom());
                foreach ($partner->getAffectations() as $affectation) {
                    if ($affectation->getSignalement()->getId() === $signalement->getId()) {
                        $affectation->setStatut(Affectation::STATUS_WAIT);
                        break;
                    }
                }
            }
            $signalement->setStatut(Signalement::STATUS_ACTIVE);
            $suiviManager->createSuivi(
                user: $user,
                signalement: $signalement,
                description: 'Signalement rouvert pour '.$reopenFor,
                type: Suivi::TYPE_AUTO,
                isPublic: '1' === $request->get('publicSuivi'),
            );
            $this->addFlash('success', 'Signalement rouvert avec succès !');
        } else {
            $this->addFlash('error', 'Erreur lors de la réouverture du signalement !');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid:signalement}/switch', name: 'back_signalement_switch_value', methods: 'POST')]
    public function switchValue(Signalement $signalement, Request $request, EntityManagerInterface $entityManager): RedirectResponse|JsonResponse
    {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);
        if ($this->isCsrfTokenValid('signalement_switch_value_'.$signalement->getUuid(), $request->get('_token'))) {
            $value = $request->get('value');

            $tag = $entityManager->getRepository(Tag::class)->find((int) $value);
            if ($signalement->getTags()->contains($tag)) {
                $signalement->removeTag($tag);
            } else {
                $signalement->addTag($tag);
            }

            $entityManager->persist($signalement);
            $entityManager->flush();

            return $this->json(['response' => 'success']);
        }

        return $this->json(['response' => 'error'], 400);
    }

    #[Route('/{uuid:signalement}/set-rnb', name: 'back_signalement_set_rnb', methods: 'POST')]
    public function setRnbId(
        Signalement $signalement,
        Request $request,
        RnbService $rnbService,
        SignalementManager $signalementManager,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);
        $rnbId = $request->get('rnbId');
        $token = $request->get('_token');
        if (!$this->isCsrfTokenValid('signalement_set_rnb_'.$signalement->getUuid(), $token)) {
            $this->addFlash('error', 'Le jeton CSRF est invalide. Veuillez réessayer.');

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }
        if (!empty($signalement->getGeoloc())) {
            $this->addFlash('error', 'Le signalement a déjà une géolocalisation.');

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }
        $building = $rnbService->getBuilding($rnbId);
        if (!$building) {
            $this->addFlash('error', 'Le bâtiment n\'a pas été trouvé.');
        } else {
            $signalement->setRnbIdOccupant($building->getRnbId());
            $signalement->setGeoloc(['lat' => $building->getLat(), 'lng' => $building->getLng()]);
            $signalementManager->flush();
            $this->addFlash('success', 'Le bâtiment a été mis à jour avec succès.');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }
}
