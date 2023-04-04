<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Tag;
use App\Service\NotificationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/bo/signalements')]
class BackSignalementActionController extends AbstractController
{
    #[Route('/{uuid}/validation/response', name: 'back_signalement_validation_response', methods: 'GET')]
    public function validationResponseSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine, UrlGeneratorInterface $urlGenerator, NotificationService $notificationService): Response
    {
        $this->denyAccessUnlessGranted('SIGN_VALIDATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_validation_response_'.$signalement->getId(), $request->get('_token'))
            && $response = $request->get('signalement-validation-response')) {
            if (isset($response['accept'])) {
                $statut = Signalement::STATUS_ACTIVE;
                $description = 'validé';
                $signalement->setValidatedAt(new DateTimeImmutable());
                $signalement->setCodeSuivi(md5(uniqid()));
                $toRecipients = $signalement->getMailUsagers();
                foreach ($toRecipients as $toRecipient) {
                    $notificationService->send(
                        NotificationService::TYPE_SIGNALEMENT_VALIDATION,
                        [$toRecipient],
                        [
                            'signalement' => $signalement,
                            'lien_suivi' => $urlGenerator->generate(
                                'front_suivi_signalement',
                                [
                                    'code' => $signalement->getCodeSuivi(),
                                    'from' => $toRecipient,
                                ],
                                0
                            ),
                        ],
                        $signalement->getTerritory()
                    );
                }
            } else {
                $statut = Signalement::STATUS_REFUSED;
                $description = 'cloturé car non-valide avec le motif suivant :<br>'.$response['suivi'];

                $toRecipients = $signalement->getMailUsagers();
                $notificationService->send(
                    NotificationService::TYPE_SIGNALEMENT_REFUSAL,
                    $toRecipients,
                    [
                        'signalement' => $signalement,
                        'motif' => $response['suivi'],
                    ],
                    $signalement->getTerritory()
                );
            }
            $suivi = new Suivi();
            $suivi->setSignalement($signalement);
            $suivi->setDescription('Signalement '.$description);
            $suivi->setCreatedBy($this->getUser());
            $suivi->setIsPublic(true);
            $suivi->setType(SUIVI::TYPE_AUTO);
            $signalement->setStatut($statut);
            $doctrine->getManager()->persist($signalement);
            $doctrine->getManager()->persist($suivi);
            $doctrine->getManager()->flush();

            $this->addFlash('success', 'Statut du signalement mis à jour avec succés !');
        } else {
            $this->addFlash('error', 'Une erreur est survenue...');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/suivi/add', name: 'back_signalement_add_suivi', methods: 'POST')]
    public function addSuiviSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine, NotificationService $notificationService, UrlGeneratorInterface $urlGenerator): Response
    {
        $this->denyAccessUnlessGranted('COMMENT_CREATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_add_suivi_'.$signalement->getId(), $request->get('_token'))
            && $form = $request->get('signalement-add-suivi')) {
            $suivi = new Suivi();
            $content = $form['content'];
            $content = preg_replace('/<p[^>]*>/', '', $content); // Remove the start <p> or <p attr="">
            $content = str_replace('</p>', '<br />', $content); // Replace the end
            $suivi->setDescription($content);
            $suivi->setIsPublic($form['isPublic']);
            $suivi->setSignalement($signalement);
            $suivi->setCreatedBy($this->getUser());
            $suivi->setType(SUIVI::TYPE_PARTNER);
            $doctrine->getManager()->persist($suivi);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Suivi publié avec succès !');
        } else {
            $this->addFlash('error', 'Une erreur est survenu lors de la publication');
        }

        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]).'#suivis');
    }

    #[Route('/{uuid}/reopen', name: 'back_signalement_reopen')]
    public function reopenSignalement(
        Signalement $signalement,
        Request $request,
        ManagerRegistry $doctrine,
        WorkflowInterface $affectationManagementStateMachine
        ): RedirectResponse|JsonResponse
    {
//        $this->denyAccessUnlessGranted('SIGN_REOPEN', $signalement);
        if ($this->isCsrfTokenValid('signalement_reopen_'.$signalement->getId(), $request->get('_token')) && $response = $request->get('signalement-action')) {
            if ($this->isGranted('ROLE_ADMIN_TERRITORY') && isset($response['reopenAll'])) {
                $signalement->getAffectations()->filter(function (Affectation $affectation) use ($doctrine, $affectationManagementStateMachine) {
                    $affectationManagementStateMachine->apply($affectation, 'to_reopened');
                    $affectation->setStatut(Affectation::STATUS_WAIT) && $doctrine->getManager()->persist($affectation);
                });
                $reopenFor = 'tous les partenaires';
            } else {
                $this->getUser()->getPartner()->getAffectations()->filter(function (Affectation $affectation) use ($signalement, $doctrine, $affectationManagementStateMachine) {
                    if ($affectation->getSignalement()->getId() === $signalement->getId()) {
                        $affectationManagementStateMachine->apply($affectation, 'to_reopened');
                        $affectation->setStatut(Affectation::STATUS_WAIT) && $doctrine->getManager()->persist($affectation);
                    }
                });
                $reopenFor = mb_strtoupper($this->getUser()->getPartner()->getNom());
            }
            $signalement->setStatut(Signalement::STATUS_ACTIVE);
            $currentCodeSuivi = $signalement->getCodeSuivi();
            if (empty($currentCodeSuivi)) {
                $signalement->setCodeSuivi(md5(uniqid()));
            }
            $doctrine->getManager()->persist($signalement);
            $suivi = new Suivi();
            $suivi->setSignalement($signalement);
            $suivi->setDescription('Signalement rouvert pour '.$reopenFor);
            $suivi->setCreatedBy($this->getUser());
            $suivi->setIsPublic(true);
            $suivi->setType(SUIVI::TYPE_AUTO);
            $doctrine->getManager()->persist($suivi);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Signalement rouvert avec succés !');
        } else {
            $this->addFlash('error', 'Erreur lors de la réouverture du signalement! ');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/switch', name: 'back_signalement_switch_value', methods: 'POST')]
    public function switchValue(Signalement $signalement, Request $request, EntityManagerInterface $entityManager): RedirectResponse|JsonResponse
    {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);
        if ($this->isCsrfTokenValid('signalement_switch_value_'.$signalement->getUuid(), $request->get('_token'))) {
            $return = 0;
            $item = $request->get('item');
            $getMethod = 'get'.$item;
            $setMethod = 'set'.$item;
            $value = $request->get('value');
            if ('Tag' === $item) {
                $tag = $entityManager->getRepository(Tag::class)->find((int) $value);
                if ($signalement->getTags()->contains($tag)) {
                    $signalement->removeTag($tag);
                } else {
                    $signalement->addTag($tag);
                }
            } else {
                if ('DateVisite' === $item) {
                    $value = new DateTimeImmutable($value);
                    $item = 'La date de visite';
                }
                if (!$value) {
                    $value = !(int) $signalement->$getMethod() ?? 1;
                    $return = 1;
                }

                $signalement->$setMethod($value);
            }
            $entityManager->persist($signalement);
            $entityManager->flush();
            if ('CodeProcedure' === $item) {
                $item = 'Le type de procédure';
            }
            if (\is_bool($value) || 'Tag' === $item) {
                return $this->json(['response' => 'success', 'return' => $return]);
            }
            $this->addFlash('success', $item.' a bien été enregistré !');

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }

        return $this->json(['response' => 'error'], 400);
    }
}
