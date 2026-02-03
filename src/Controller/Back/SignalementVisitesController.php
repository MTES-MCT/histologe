<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\VisiteRequest;
use App\Entity\Enum\Qualification;
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
use App\Repository\AffectationRepository;
use App\Repository\InterventionRepository;
use App\Security\Voter\InterventionVoter;
use App\Security\Voter\SignalementVoter;
use App\Service\Files\FilenameGenerator;
use App\Service\RequestDataExtractor;
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
    private const string SUCCESS_MSG_ADD = 'La date de visite a bien été définie.';
    private const string SUCCESS_MSG_CONFIRM = 'Les informations de la visite ont bien été enregistrées.';

    private function getSecurityRedirect(
        Request $request,
        string $tokenName,
        Intervention $intervention,
        InterventionRepository $interventionRepository,
        AffectationRepository $affectationRepository, ): ?Response
    {
        if (!$this->isCsrfTokenValid($tokenName, (string) $request->get('_token'))) {
            $flashMessages[] = ['type' => 'error', 'title' => 'Erreur', 'message' => 'Erreur de sécurisation de l\'envoi de données.'];

            return $this->buildVisitesAjaxResponse($intervention, $interventionRepository, $affectationRepository, $flashMessages);
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

    private function buildVisitesAjaxResponse(
        Intervention $intervention,
        InterventionRepository $interventionRepository,
        AffectationRepository $affectationRepository,
        array $flashMessages,
        bool $closeModalAndReload = true,
    ): Response {
        $signalement = $intervention->getSignalement();
        $visites = $interventionRepository->getOrderedVisitesForSignalement($signalement);
        $pendingVisites = $interventionRepository->getPendingVisitesForSignalement($signalement);
        $partnerVisite = $affectationRepository->findAffectationWithQualification(Qualification::VISITES, $signalement);

        $htmlTargetContents = [
            [
                'target' => '#list-visites',
                'content' => $this->renderView('back/signalement/view/visites/visites-list.html.twig',
                    [
                        'signalement' => $signalement,
                        'visites' => $visites,
                        'partnersCanVisite' => $partnerVisite,
                        'pendingVisites' => $pendingVisites,
                    ]),
            ],
        ];
        $functions = [
            [
                'name' => 'reloadTinyMCE',
                'args' => ['textarea.editor'],
            ],
            [
                'name' => 'attachAjaxFormHandlers',
                'args' => [],
            ],
            [
                'name' => 'initSearchCheckboxWidgets',
                'args' => [],
            ],
        ];

        if ($closeModalAndReload) {
            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
        }

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => false]);
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
        InterventionRepository $interventionRepository,
        AffectationRepository $affectationRepository,
    ): Response {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_ADD_VISITE, $signalement);

        if (!$this->isCsrfTokenValid('signalement_add_visit_'.$signalement->getId(), (string) $request->get('_token'))) {
            $this->addFlash('error', "Erreur de sécurisation de l'envoi de données.");

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }

        $fileName = $this->getUploadedFile($request, 'visite-add', $uploadHandler, $filenameGenerator);

        $requestData = $request->request->all();
        $requestAddData = RequestDataExtractor::getArray($requestData, 'visite-add');
        $idPartner = 'extern' === $requestAddData['partner'] ? null : $requestAddData['partner'];
        $visiteRequest = new VisiteRequest(
            idIntervention: $requestAddData['intervention'] ?? null,
            date: $requestAddData['date'],
            time: $requestAddData['time'],
            timezone: $timezoneProvider->getTimezone(),
            idPartner: $idPartner,
            externalOperator: empty($idPartner) ? $requestAddData['externalOperator'] ?? null : null,
            commentBeforeVisite: $requestAddData['commentBeforeVisite'] ?? null,
            details: $requestAddData['details'] ?? null,
            concludeProcedure: $requestAddData['concludeProcedure'] ?? null,
            isVisiteDone: $requestAddData['visiteDone'] ?? null,
            isOccupantPresent: $requestAddData['occupantPresent'] ?? null,
            isProprietairePresent: $requestAddData['proprietairePresent'] ?? null,
            isUsagerNotified: !empty($requestAddData['notifyUsager']),
            document: $fileName,
        );
        /** @var User $user */
        $user = $this->getUser();
        $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
        $errorMessage = $this->validateRequest($visiteRequest, $validator);
        if ($errorMessage) {
            $this->addFlash('error', ['title' => 'Erreur', 'message' => \sprintf("Erreurs lors de l'enregistrement de la visite : %s, veuillez réessayer.", $errorMessage)]);
        } elseif ($intervention = $interventionManager->createVisiteFromRequest($signalement, $visiteRequest, $partner)) {
            $todayDate = new \DateTimeImmutable();
            if ($intervention->getScheduledAt()->format('Y-m-d') <= $todayDate->format('Y-m-d')) {
                $flashMessages[] = ['type' => 'success', 'title' => 'Visite ajoutée', 'message' => self::SUCCESS_MSG_CONFIRM];
            } else {
                $flashMessages[] = ['type' => 'success', 'title' => 'Visite ajoutée', 'message' => self::SUCCESS_MSG_ADD];
                /** @var User $user */
                $user = $this->getUser();
                $eventDispatcher->dispatch(
                    new InterventionCreatedEvent(
                        $intervention,
                        $user,
                        $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory())
                    ),
                    InterventionCreatedEvent::NAME
                );
            }

            return $this->buildVisitesAjaxResponse($intervention, $interventionRepository, $affectationRepository, $flashMessages);
        } else {
            $this->addFlash('error', ['title' => 'Erreur', 'message' => 'Erreur lors de l\'enregistrement de la visite, veuillez réessayer.']);
        }

        return $this->redirectToRoute('back_signalements_index');
    }

    #[Route('/{uuid:signalement}/visites/annuler', name: 'back_signalement_visite_cancel', methods: 'POST')]
    public function cancelVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
        InterventionRepository $interventionRepository,
        AffectationRepository $affectationRepository,
    ): Response {
        $requestData = $request->request->all();
        $requestCancelData = RequestDataExtractor::getArray($requestData, 'visite-cancel');

        $intervention = $interventionRepository->findOneBy(['id' => $requestCancelData['intervention'], 'signalement' => $signalement]);
        if (!$intervention) {
            $this->addFlash('error', "Cette visite n'existe pas.");

            return $this->redirectToRoute('back_signalements_index');
        }
        $this->denyAccessUnlessGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, $intervention);

        if ($intervention->hasScheduledDatePassed()) {
            $flashMessages[] = ['type' => 'error', 'title' => 'Erreur', 'message' => 'Cette visite est déja passée et ne peut pas être annulée, merci de la noter comme non-effectuée.'];

            return $this->buildVisitesAjaxResponse($intervention, $interventionRepository, $affectationRepository, $flashMessages);
        }

        $errorRedirect = $this->getSecurityRedirect(
            $request,
            'signalement_cancel_visit_'.$requestCancelData['intervention'],
            $intervention,
            $interventionRepository,
            $affectationRepository,
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $visiteRequest = new VisiteRequest(
            idIntervention: $requestCancelData['intervention'],
            details: $requestCancelData['details'],
        );
        /** @var User $user */
        $user = $this->getUser();
        if ($interventionManager->cancelVisiteFromRequest($visiteRequest, $user->getPartnerInTerritory($signalement->getTerritory()))) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Visite annulée', 'message' => 'La visite a bien été annulée.'];
        } else {
            $flashMessages[] = ['type' => 'error', 'title' => 'Erreur', 'message' => 'Erreur lors de l\'annulation de la visite.'];
        }

        return $this->buildVisitesAjaxResponse($intervention, $interventionRepository, $affectationRepository, $flashMessages);
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
        AffectationRepository $affectationRepository,
    ): Response {
        $requestData = $request->request->all();
        $requestRescheduleData = RequestDataExtractor::getArray($requestData, 'visite-reschedule');

        $intervention = $interventionRepository->findOneBy(['id' => $requestRescheduleData['intervention'], 'signalement' => $signalement]);
        if (!$intervention) {
            $this->addFlash('error', "Cette visite n'existe pas.");

            return $this->redirectToRoute('back_signalements_index');
        }
        $this->denyAccessUnlessGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, $intervention);

        $errorRedirect = $this->getSecurityRedirect(
            $request,
            'signalement_reschedule_visit_'.$requestRescheduleData['intervention'],
            $intervention,
            $interventionRepository,
            $affectationRepository,
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
            commentBeforeVisite: $requestRescheduleData['commentBeforeVisite'] ?? null,
            details: $requestRescheduleData['details'] ?? null,
            concludeProcedure: $requestRescheduleData['concludeProcedure'] ?? null,
            isVisiteDone: $requestRescheduleData['visiteDone'] ?? null,
            isOccupantPresent: $requestRescheduleData['occupantPresent'] ?? null,
            isProprietairePresent: $requestRescheduleData['proprietairePresent'] ?? null,
            isUsagerNotified: !empty($requestRescheduleData['notifyUsager']),
            document: $fileName,
        );
        /** @var User $user */
        $user = $this->getUser();
        $partner = $user->getPartnerInTerritory($signalement->getTerritory());
        $errorMessage = $this->validateRequest($visiteRequest, $validator);
        if ($errorMessage) {
            $flashMessages[] = ['type' => 'error', 'title' => 'Erreur', 'message' => \sprintf('Erreurs lors de la modification de la visite : %s, veuillez réessayer.', $errorMessage)];
        } elseif ($intervention = $interventionManager->rescheduleVisiteFromRequest($signalement, $visiteRequest, $partner)) {
            if ($intervention->getScheduledAt()->format('Y-m-d') <= (new \DateTimeImmutable())->format('Y-m-d')) {
                $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => self::SUCCESS_MSG_CONFIRM];
            } else {
                $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => self::SUCCESS_MSG_ADD];
                $eventDispatcher->dispatch(
                    new InterventionRescheduledEvent(
                        $intervention,
                        $user,
                        $previousDate,
                        $partner
                    ), InterventionRescheduledEvent::NAME
                );
            }
        } else {
            $flashMessages[] = ['type' => 'error', 'title' => 'Erreur', 'message' => 'Erreur lors de la modification de la visite, veuillez réessayer.'];
        }

        return $this->buildVisitesAjaxResponse($intervention, $interventionRepository, $affectationRepository, $flashMessages);
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
        AffectationRepository $affectationRepository,
        ValidatorInterface $validator,
    ): Response {
        $requestData = $request->request->all();
        $requestConfirmData = RequestDataExtractor::getArray($requestData, 'visite-confirm');

        $intervention = $interventionRepository->findOneBy(['id' => $requestConfirmData['intervention'], 'signalement' => $signalement]);
        if (!$intervention) {
            $this->addFlash('error', "Cette visite n'existe pas.");

            return $this->redirectToRoute('back_signalements_index');
        }
        $this->denyAccessUnlessGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, $intervention);

        $errorRedirect = $this->getSecurityRedirect(
            $request,
            'signalement_confirm_visit_'.$requestConfirmData['intervention'],
            $intervention,
            $interventionRepository,
            $affectationRepository,
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $fileName = $this->getUploadedFile($request, 'visite-confirm', $uploadHandler, $filenameGenerator);

        $visiteRequest = new VisiteRequest(
            idIntervention: $requestConfirmData['intervention'],
            date: $intervention->getScheduledAt()->format('Y-m-d'),
            idPartner: $intervention->getPartner()?->getId(),
            externalOperator: $intervention->getExternalOperator(),
            details: $requestConfirmData['details'],
            concludeProcedure: $requestConfirmData['concludeProcedure'] ?? null,
            isVisiteDone: $requestConfirmData['visiteDone'] ?? null,
            isOccupantPresent: $requestConfirmData['occupantPresent'] ?? null,
            isProprietairePresent: $requestConfirmData['proprietairePresent'] ?? null,
            document: $fileName,
        );
        /** @var User $user */
        $user = $this->getUser();

        $errorMessage = $this->validateRequest($visiteRequest, $validator);
        if ($errorMessage) {
            $flashMessages[] = ['type' => 'error', 'title' => 'Erreur', 'message' => \sprintf('Erreurs lors de la conclusion de la visite : %s, veuillez réessayer.', $errorMessage)];
        }
        if ($interventionManager->confirmVisiteFromRequest($visiteRequest, $user->getPartnerInTerritory($signalement->getTerritory()))) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => self::SUCCESS_MSG_CONFIRM];

            return $this->buildVisitesAjaxResponse($intervention, $interventionRepository, $affectationRepository, $flashMessages);
        }
        $flashMessages[] = ['type' => 'error', 'title' => 'Erreur', 'message' => 'Erreur lors de la conclusion de la visite, veuillez réessayer.'];

        return $this->buildVisitesAjaxResponse($intervention, $interventionRepository, $affectationRepository, $flashMessages, false);
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
        AffectationRepository $affectationRepository,
    ): Response {
        $requestData = $request->request->all();
        $requestEditData = RequestDataExtractor::getArray($requestData, 'visite-edit');

        $intervention = !empty($requestEditData['intervention'])
            ? $interventionRepository->findOneBy(['id' => $requestEditData['intervention'], 'signalement' => $signalement])
            : null;
        if (!$intervention) {
            $this->addFlash('error', "Cette visite n'existe pas.");

            return $this->redirectToRoute('back_signalements_index');
        }
        $this->denyAccessUnlessGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, $intervention);

        $errorRedirect = $this->getSecurityRedirect(
            $request,
            'signalement_edit_visit_'.$requestEditData['intervention'],
            $intervention,
            $interventionRepository,
            $affectationRepository,
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $fileName = $this->getUploadedFile($request, 'visite-edit', $uploadHandler, $filenameGenerator);

        if (!isset($requestEditData['notifyUsager'])) {
            $requestEditData['notifyUsager'] = $intervention->getNotifyUsager();
        }
        $visiteRequest = new VisiteRequest(
            idIntervention: $requestEditData['intervention'],
            details: $requestEditData['details'],
            concludeProcedure: $requestEditData['concludeProcedure'] ?? [],
            isUsagerNotified: $requestEditData['notifyUsager'] ?? false,
            document: $fileName,
        );
        /** @var User $user */
        $user = $this->getUser();
        $partner = $user->getPartnerInTerritory($signalement->getTerritory());

        if ($interventionManager->editVisiteFromRequest($visiteRequest, $partner)) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => self::SUCCESS_MSG_CONFIRM];
            $eventDispatcher->dispatch(new InterventionEditedEvent(
                $intervention,
                $user,
                $visiteRequest->isUsagerNotified(),
                $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory())
            ), InterventionEditedEvent::NAME);

            return $this->buildVisitesAjaxResponse($intervention, $interventionRepository, $affectationRepository, $flashMessages);
        }
        $flashMessages[] = ['type' => 'error', 'title' => 'Erreur', 'message' => 'Erreur lors de la conclusion de la visite, veuillez réessayer.'];

        return $this->buildVisitesAjaxResponse($intervention, $interventionRepository, $affectationRepository, $flashMessages, false);
    }

    #[Route('/visites/{intervention}/delete-rapport', name: 'back_signalement_visite_deleterapport')]
    public function deleteRapportVisiteFromSignalement(
        Intervention $intervention,
        Request $request,
        EntityManagerInterface $entityManager,
        UploadHandlerService $uploadHandlerService,
        AffectationRepository $affectationRepository,
        InterventionRepository $interventionRepository,
    ): Response {
        if (!$intervention->getRapportDeVisite()) {
            $this->addFlash('error', "Ce rapport n'existe pas.");

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $intervention->getSignalement()->getUuid()]);
        }
        $this->denyAccessUnlessGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, $intervention);

        $errorRedirect = $this->getSecurityRedirect(
            $request,
            'delete_rapport',
            $intervention,
            $interventionRepository,
            $affectationRepository,
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $file = $intervention->getRapportDeVisite();
        $uploadHandlerService->deleteFileInBucket($file);
        $entityManager->remove($file);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Document supprimé', 'message' => 'Le rapport de visite a bien été supprimé.'];

        return $this->buildVisitesAjaxResponse($intervention, $interventionRepository, $affectationRepository, $flashMessages);
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
