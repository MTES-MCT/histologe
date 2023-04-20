<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\VisiteRequest;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\InterventionCreatedEvent;
use App\Event\InterventionRescheduledEvent;
use App\Exception\File\MaxUploadSizeExceededException;
use App\Manager\InterventionManager;
use App\Repository\InterventionRepository;
use App\Service\UploadHandlerService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/bo/signalements')]
class SignalementVisitesController extends AbstractController
{
    private const SUCCESS_MSG_ADD = 'La date de visite a bien été définie.';
    private const SUCCESS_MSG_CONFIRM = 'Les informations de la visite ont bien été renseignées.';

    private function getSecurityRedirect(Signalement $signalement, Request $request, string $tokenName): ?Response
    {
        $this->denyAccessUnlessGranted('SIGN_VIEW', $signalement);
        if (Signalement::STATUS_ARCHIVED === $signalement->getStatut()) {
            $this->addFlash('error', "Ce signalement a été archivé et n'est pas consultable.");

            return $this->redirectToRoute('back_index');
        }

        if (!$this->isCsrfTokenValid($tokenName, $request->get('_token'))) {
            $this->addFlash('error', "Erreur de sécurisation de l'envoi de données.");

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }

        return null;
    }

    private function getUploadedFile(
        Request $request,
        string $inputName,
        SluggerInterface $slugger,
        UploadHandlerService $uploadHandler,
    ): ?string {
        $files = $request->files->get($inputName);
        if (empty($files) || empty($files['rapport'])) {
            return null;
        }

        $file = $files['rapport'];
        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
        try {
            return $uploadHandler->uploadFromFile($file, $newFilename);
        } catch (MaxUploadSizeExceededException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return null;
        }
    }

    #[Route('/{uuid}/visites/ajouter', name: 'back_signalement_visite_add')]
    public function addVisiteToSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
        SluggerInterface $slugger,
        UploadHandlerService $uploadHandler,
        EventDispatcherInterface $eventDispatcher,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_ADD_VISITE', $signalement);

        $errorRedirect = $this->getSecurityRedirect(
            $signalement,
            $request,
            'signalement_add_visit_'.$signalement->getId()
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $fileName = $this->getUploadedFile($request, 'visite-add', $slugger, $uploadHandler);

        $requestAddData = $request->get('visite-add');
        $visiteRequest = new VisiteRequest(
            date: $requestAddData['date'],
            idPartner: $requestAddData['partner'],
            idIntervention: $requestAddData['intervention'] ?? null,
            details: $requestAddData['details'] ?? null,
            concludeProcedure: $requestAddData['concludeProcedure'] ?? null,
            isVisiteDone: $requestAddData['visiteDone'] ?? null,
            isOccupantPresent: $requestAddData['occupantPresent'] ?? null,
            document: $fileName,
        );

        if ($intervention = $interventionManager->createVisiteFromRequest($signalement, $visiteRequest)) {
            $todayDate = new DateTime();
            if ($intervention->getDate() <= $todayDate) {
                $this->addFlash('success', self::SUCCESS_MSG_CONFIRM);
            } else {
                $this->addFlash('success', self::SUCCESS_MSG_ADD);
                /** @var User $user */
                $user = $this->getUser();
                $eventDispatcher->dispatch(new InterventionCreatedEvent($intervention, $user), InterventionCreatedEvent::NAME);
            }
        } else {
            $this->addFlash('error', "Erreur lors de l'enregistrement de la visite.");
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/visites/annuler', name: 'back_signalement_visite_cancel')]
    public function cancelVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
        InterventionRepository $interventionRepository,
    ): Response {
        $requestData = $request->get('visite-cancel');

        $intervention = $interventionRepository->findOneBy(['id' => $requestData['intervention']]);
        if (!$intervention) {
            return null;
        }
        $this->denyAccessUnlessGranted('INTERVENTION_EDIT_VISITE', $intervention);

        $errorRedirect = $this->getSecurityRedirect(
            $signalement,
            $request,
            'signalement_cancel_visit_'.$requestData['intervention']
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $visiteRequest = new VisiteRequest(
            idIntervention: $requestData['intervention'],
            details: $requestData['details'],
        );

        if ($interventionManager->cancelVisiteFromRequest($visiteRequest)) {
            $this->addFlash('success', 'La visite a bien été annulée.');
        } else {
            $this->addFlash('error', "Erreur lors de l'annulation de la visite.");
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/visites/reprogrammer', name: 'back_signalement_visite_reschedule')]
    public function rescheduleVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
        InterventionRepository $interventionRepository,
        SluggerInterface $slugger,
        UploadHandlerService $uploadHandler,
        EventDispatcherInterface $eventDispatcher,
    ): Response {
        $requestRescheduleData = $request->get('visite-reschedule');

        $intervention = $interventionRepository->findOneBy(['id' => $requestRescheduleData['intervention']]);
        if (!$intervention) {
            return null;
        }
        $this->denyAccessUnlessGranted('INTERVENTION_EDIT_VISITE', $intervention);

        $errorRedirect = $this->getSecurityRedirect(
            $signalement,
            $request,
            'signalement_reschedule_visit_'.$requestRescheduleData['intervention']
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $previousDate = $intervention->getDate();
        $fileName = $this->getUploadedFile($request, 'visite-reschedule', $slugger, $uploadHandler);

        $visiteRequest = new VisiteRequest(
            date: $requestRescheduleData['date'],
            idPartner: $requestRescheduleData['partner'],
            idIntervention: $requestRescheduleData['intervention'],
            details: $requestRescheduleData['details'] ?? null,
            concludeProcedure: $requestRescheduleData['concludeProcedure'] ?? null,
            isVisiteDone: $requestRescheduleData['visiteDone'] ?? null,
            isOccupantPresent: $requestRescheduleData['occupantPresent'] ?? null,
            document: $fileName,
        );

        if ($intervention = $interventionManager->rescheduleVisiteFromRequest($signalement, $visiteRequest)) {
            $todayDate = new DateTime();
            if ($intervention->getDate() <= $todayDate) {
                $this->addFlash('success', self::SUCCESS_MSG_CONFIRM);
            } else {
                $this->addFlash('success', self::SUCCESS_MSG_ADD);
                /** @var User $user */
                $user = $this->getUser();
                $eventDispatcher->dispatch(new InterventionRescheduledEvent($intervention, $user, $previousDate), InterventionRescheduledEvent::NAME);
            }
        } else {
            $this->addFlash('error', 'Erreur lors de la modification de la visite.');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/visites/confirmer', name: 'back_signalement_visite_confirm')]
    public function confirmVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
        InterventionRepository $interventionRepository,
        SluggerInterface $slugger,
        UploadHandlerService $uploadHandler,
    ): Response {
        $requestData = $request->get('visite-confirm');

        $intervention = $interventionRepository->findOneBy(['id' => $requestData['intervention']]);
        if (!$intervention) {
            return null;
        }
        $this->denyAccessUnlessGranted('INTERVENTION_EDIT_VISITE', $intervention);

        $errorRedirect = $this->getSecurityRedirect(
            $signalement,
            $request,
            'signalement_confirm_visit_'.$requestData['intervention']
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $fileName = $this->getUploadedFile($request, 'visite-confirm', $slugger, $uploadHandler);

        $visiteRequest = new VisiteRequest(
            idIntervention: $requestData['intervention'],
            details: $requestData['details'],
            concludeProcedure: $requestData['concludeProcedure'],
            isVisiteDone: $requestData['visiteDone'],
            isOccupantPresent: $requestData['occupantPresent'],
            document: $fileName,
        );

        if ($interventionManager->confirmVisiteFromRequest($visiteRequest)) {
            $this->addFlash('success', self::SUCCESS_MSG_CONFIRM);
        } else {
            $this->addFlash('error', 'Erreur lors de la conclusion de la visite.');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/visites/editer', name: 'back_signalement_visite_edit')]
    public function editVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
        InterventionRepository $interventionRepository,
        SluggerInterface $slugger,
        UploadHandlerService $uploadHandler,
    ): Response {
        $requestData = $request->get('visite-edit');

        $intervention = $interventionRepository->findOneBy(['id' => $requestData['intervention']]);
        if (!$intervention) {
            return null;
        }
        $this->denyAccessUnlessGranted('INTERVENTION_EDIT_VISITE', $intervention);

        $errorRedirect = $this->getSecurityRedirect(
            $signalement,
            $request,
            'signalement_edit_visit_'.$requestData['intervention']
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $fileName = $this->getUploadedFile($request, 'visite-edit', $slugger, $uploadHandler);

        $visiteRequest = new VisiteRequest(
            idIntervention: $requestData['intervention'],
            details: $requestData['details'],
            isUsagerNotified: !empty($requestData['notifyUsager']),
            document: $fileName,
        );

        if ($interventionManager->editVisiteFromRequest($visiteRequest)) {
            $this->addFlash('success', self::SUCCESS_MSG_CONFIRM);
        } else {
            $this->addFlash('error', 'Erreur lors de la conclusion de la visite.');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }
}
