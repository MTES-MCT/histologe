<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\VisiteRequest;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\InterventionCreatedEvent;
use App\Event\InterventionEditedEvent;
use App\Event\InterventionRescheduledEvent;
use App\Exception\File\EmptyFileException;
use App\Exception\File\MaxUploadSizeExceededException;
use App\Exception\File\UnsupportedFileFormatException;
use App\Manager\InterventionManager;
use App\Repository\InterventionRepository;
use App\Service\Files\FilenameGenerator;
use App\Service\TimezoneProvider;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo/signalements')]
class SignalementVisitesController extends AbstractController
{
    private const SUCCESS_MSG_ADD = 'La date de visite a bien été définie.';
    private const SUCCESS_MSG_CONFIRM = 'Les informations de la visite ont bien été renseignées.';

    private function getSecurityRedirect(Signalement $signalement, Request $request, string $tokenName): ?Response
    {
        if (!$this->isCsrfTokenValid($tokenName, $request->get('_token'))) {
            $this->addFlash('error', "Erreur de sécurisation de l'envoi de données.");

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }

        return null;
    }

    private function getUploadedFile(
        Request $request,
        string $inputName,
        UploadHandlerService $uploadHandler,
        FilenameGenerator $filenameGenerator,
    ): ?string {
        $files = $request->files->get($inputName);
        if (empty($files) || empty($files['rapport'])) {
            return null;
        }

        $file = $files['rapport'];
        $newFilename = $filenameGenerator->generate($file);
        try {
            return $uploadHandler->uploadFromFile($file, $newFilename);
        } catch (MaxUploadSizeExceededException|UnsupportedFileFormatException|EmptyFileException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return null;
        }
    }

    /**
     * @throws \Exception
     */
    #[Route('/{uuid:signalement}/visites/ajouter', name: 'back_signalement_visite_add', methods: 'POST')]
    public function addVisiteToSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
        UploadHandlerService $uploadHandler,
        EventDispatcherInterface $eventDispatcher,
        FilenameGenerator $filenameGenerator,
        ValidatorInterface $validator,
        TimezoneProvider $timezoneProvider,
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

        $fileName = $this->getUploadedFile($request, 'visite-add', $uploadHandler, $filenameGenerator);

        $requestAddData = $request->get('visite-add');
        $idPartner = 'extern' === $requestAddData['partner'] ? null : $requestAddData['partner'];
        $visiteRequest = new VisiteRequest(
            idIntervention: $requestAddData['intervention'] ?? null,
            date: $requestAddData['date'],
            time: $requestAddData['time'],
            timezone: $timezoneProvider->getTimezone(),
            idPartner: $idPartner,
            externalOperator: empty($idPartner) ? $requestAddData['externalOperator'] ?? null : null,
            details: $requestAddData['details'] ?? null,
            concludeProcedure: $requestAddData['concludeProcedure'] ?? null,
            isVisiteDone: $requestAddData['visiteDone'] ?? null,
            isOccupantPresent: $requestAddData['occupantPresent'] ?? null,
            isProprietairePresent: $requestAddData['proprietairePresent'] ?? null,
            isUsagerNotified: !empty($requestAddData['notifyUsager']),
            document: $fileName,
        );

        $errorMessage = $this->validateRequest($visiteRequest, $validator);
        if ($errorMessage) {
            $this->addFlash('error', \sprintf("Erreurs lors de l'enregistrement de la visite : %s", $errorMessage));
        } elseif ($intervention = $interventionManager->createVisiteFromRequest($signalement, $visiteRequest)) {
            $todayDate = new \DateTimeImmutable();
            if ($intervention->getScheduledAt()->format('Y-m-d') <= $todayDate->format('Y-m-d')) {
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

    #[Route('/{uuid:signalement}/visites/annuler', name: 'back_signalement_visite_cancel', methods: 'POST')]
    public function cancelVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
        InterventionRepository $interventionRepository,
    ): Response {
        $requestData = $request->get('visite-cancel');

        $intervention = $interventionRepository->findOneBy(['id' => $requestData['intervention'], 'signalement' => $signalement]);
        if (!$intervention) {
            $this->addFlash('error', "Cette visite n'existe pas.");

            return $this->redirectToRoute('back_signalements_index');
        }
        $this->denyAccessUnlessGranted('INTERVENTION_EDIT_VISITE', $intervention);

        if ($intervention->hasScheduledDatePassed()) {
            $this->addFlash('error', 'Cette visite est déja passée et ne peut pas être annulée, merci de la noter comme non-effectuée.');

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }

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

    /**
     * @throws \Exception
     */
    #[Route('/{uuid:signalement}/visites/reprogrammer', name: 'back_signalement_visite_reschedule', methods: 'POST')]
    public function rescheduleVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
        InterventionRepository $interventionRepository,
        UploadHandlerService $uploadHandler,
        EventDispatcherInterface $eventDispatcher,
        FilenameGenerator $filenameGenerator,
        ValidatorInterface $validator,
        TimezoneProvider $timezoneProvider,
    ): Response {
        $requestRescheduleData = $request->get('visite-reschedule');

        $intervention = $interventionRepository->findOneBy(['id' => $requestRescheduleData['intervention'], 'signalement' => $signalement]);
        if (!$intervention) {
            $this->addFlash('error', "Cette visite n'existe pas.");

            return $this->redirectToRoute('back_signalements_index');
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

        $previousDate = $intervention->getScheduledAt();
        $fileName = $this->getUploadedFile($request, 'visite-reschedule', $uploadHandler, $filenameGenerator);

        $idPartner = 'extern' === $requestRescheduleData['partner'] ? null : $requestRescheduleData['partner'];
        $visiteRequest = new VisiteRequest(
            idIntervention: $requestRescheduleData['intervention'],
            date: $requestRescheduleData['date'],
            time: $requestRescheduleData['time'],
            timezone: $timezoneProvider->getTimezone(),
            idPartner: $idPartner,
            externalOperator: empty($idPartner) ? $requestRescheduleData['externalOperator'] ?? null : null,
            details: $requestRescheduleData['details'] ?? null,
            concludeProcedure: $requestRescheduleData['concludeProcedure'] ?? null,
            isVisiteDone: $requestRescheduleData['visiteDone'] ?? null,
            isOccupantPresent: $requestRescheduleData['occupantPresent'] ?? null,
            isProprietairePresent: $requestRescheduleData['proprietairePresent'] ?? null,
            isUsagerNotified: !empty($requestRescheduleData['notifyUsager']),
            document: $fileName,
        );

        $errorMessage = $this->validateRequest($visiteRequest, $validator);
        if ($errorMessage) {
            $this->addFlash('error', \sprintf('Erreurs lors de la modification de la visite : %s', $errorMessage));
        } elseif ($intervention = $interventionManager->rescheduleVisiteFromRequest($signalement, $visiteRequest)) {
            if ($intervention->getScheduledAt()->format('Y-m-d') <= (new \DateTimeImmutable())->format('Y-m-d')) {
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

    /**
     * @throws \Exception
     */
    #[Route('/{uuid:signalement}/visites/confirmer', name: 'back_signalement_visite_confirm', methods: 'POST')]
    public function confirmVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
        InterventionRepository $interventionRepository,
        UploadHandlerService $uploadHandler,
        FilenameGenerator $filenameGenerator,
    ): Response {
        $requestData = $request->get('visite-confirm');

        $intervention = $interventionRepository->findOneBy(['id' => $requestData['intervention'], 'signalement' => $signalement]);
        if (!$intervention) {
            $this->addFlash('error', "Cette visite n'existe pas.");

            return $this->redirectToRoute('back_signalements_index');
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

        $fileName = $this->getUploadedFile($request, 'visite-confirm', $uploadHandler, $filenameGenerator);

        $visiteRequest = new VisiteRequest(
            idIntervention: $requestData['intervention'],
            details: $requestData['details'],
            concludeProcedure: $requestData['concludeProcedure'] ?? null,
            isVisiteDone: $requestData['visiteDone'],
            isOccupantPresent: $requestData['occupantPresent'],
            isProprietairePresent: $requestData['proprietairePresent'],
            document: $fileName,
        );

        if ($interventionManager->confirmVisiteFromRequest($visiteRequest)) {
            $this->addFlash('success', self::SUCCESS_MSG_CONFIRM);
        } else {
            $this->addFlash('error', 'Erreur lors de la conclusion de la visite.');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/{uuid:signalement}/visites/editer', name: 'back_signalement_visite_edit', methods: 'POST')]
    public function editVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
        InterventionRepository $interventionRepository,
        UploadHandlerService $uploadHandler,
        EventDispatcherInterface $eventDispatcher,
        FilenameGenerator $filenameGenerator,
    ): Response {
        $requestData = $request->get('visite-edit');

        $intervention = !empty($requestData['intervention'])
            ? $interventionRepository->findOneBy(['id' => $requestData['intervention'], 'signalement' => $signalement])
            : null;
        if (!$intervention) {
            $this->addFlash('error', "Cette visite n'existe pas.");

            return $this->redirectToRoute('back_signalements_index');
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

        $fileName = $this->getUploadedFile($request, 'visite-edit', $uploadHandler, $filenameGenerator);
        $isUsagerNotified = !empty($requestData['notifyUsager']);

        $visiteRequest = new VisiteRequest(
            idIntervention: $requestData['intervention'],
            details: $requestData['details'],
            isUsagerNotified: $isUsagerNotified,
            document: $fileName,
        );

        if ($interventionManager->editVisiteFromRequest($visiteRequest)) {
            $this->addFlash('success', self::SUCCESS_MSG_CONFIRM);
            /** @var User $user */
            $user = $this->getUser();
            $eventDispatcher->dispatch(new InterventionEditedEvent($intervention, $user, $isUsagerNotified), InterventionEditedEvent::NAME);
        } else {
            $this->addFlash('error', 'Erreur lors de la conclusion de la visite.');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid:signalement}/visites/{intervention}/delete-rapport', name: 'back_signalement_visite_deleterapport')]
    public function deleteRapportVisiteFromSignalement(
        Signalement $signalement,
        Intervention $intervention,
        Request $request,
        EntityManagerInterface $entityManager,
        UploadHandlerService $uploadHandlerService,
    ): Response {
        $this->denyAccessUnlessGranted('INTERVENTION_EDIT_VISITE', $intervention);
        if (!$this->isCsrfTokenValid('delete_rapport', $request->get('_token')) || $intervention->getSignalement()->getId() !== $signalement->getId() || $intervention->getFiles()->isEmpty()) {
            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }
        $file = $intervention->getFiles()->first();
        $uploadHandlerService->deleteFileInBucket($file);
        $entityManager->remove($file);
        $entityManager->flush();
        $this->addFlash('success', 'Le rapport de visite a bien été supprimé.');

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    private function validateRequest(VisiteRequest $visiteRequest, ValidatorInterface $validator): string
    {
        $errorMessage = '';

        $errors = $validator->validate($visiteRequest);
        if (\count($errors) > 0) {
            $errorMessage = '';
            foreach ($errors as $error) {
                $errorMessage .= $error->getMessage().' ';
            }
        }

        return $errorMessage;
    }
}
