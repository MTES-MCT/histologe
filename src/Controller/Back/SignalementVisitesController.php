<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\VisiteRequest;
use App\Entity\Enum\DocumentType;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\InterventionCreatedEvent;
use App\Event\InterventionEditedEvent;
use App\Event\InterventionRescheduledEvent;
use App\Exception\File\EmptyFileException;
use App\Exception\File\MaxUploadSizeExceededException;
use App\Exception\File\UnsupportedFileFormatException;
use App\Form\AddAndRescheduleVisiteType;
use App\Form\CancelVisiteType;
use App\Form\ConfirmVisiteType;
use App\Form\EditConclusionVisiteType;
use App\Manager\InterventionManager;
use App\Repository\FileRepository;
use App\Repository\InterventionRepository;
use App\Security\Voter\InterventionVoter;
use App\Security\Voter\SignalementVoter;
use App\Service\Files\FilenameGenerator;
use App\Service\MessageHelper;
use App\Service\RequestDataExtractor;
use App\Service\Signalement\PhotoHelper;
use App\Service\Signalement\SignalementDesordresProcessor;
use App\Service\TimezoneProvider;
use App\Service\UploadHandlerService;
use App\Utils\FormHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo/signalements')]
class SignalementVisitesController extends AbstractController
{
    private const string SUCCESS_MSG_ADD = 'La date de visite a bien été définie.';
    private const string SUCCESS_MSG_CONFIRM = 'Les informations de la visite ont bien été enregistrées.';

    private function getSecurityResponse(
        Request $request,
        string $tokenName,
    ): ?Response {
        $token = $request->request->get('_token') ?? $request->query->get('_token');
        if (!$this->isCsrfTokenValid($tokenName, (string) $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }

        return null;
    }

    /**
     * @return array{0: bool, 1: ?string}
     */
    private function getUploadedFile(
        Request $request,
        string $inputName,
        UploadHandlerService $uploadHandler,
        FilenameGenerator $filenameGenerator,
    ): array {
        $files = $request->files->get($inputName);
        if (empty($files) || empty($files['rapport'])) {
            return [true, null];
        }

        $file = $files['rapport'];
        $newFilename = $filenameGenerator->generate($file);
        try {
            $uploadedFilename = $uploadHandler->uploadFromFile($file, $newFilename);

            return [true, $uploadedFilename];
        } catch (MaxUploadSizeExceededException|UnsupportedFileFormatException|EmptyFileException $exception) {
            return [false, $exception->getMessage()];
        }
    }

    /**
     * @param array<array{type: string, title: string, message: string}> $flashMessages
     */
    private function buildVisitesAjaxResponse(
        Intervention $intervention,
        InterventionRepository $interventionRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        FileRepository $fileRepository,
        UrlGeneratorInterface $urlGenerator,
        array $flashMessages,
        bool $closeModalAndReload = true,
    ): Response {
        $signalement = $intervention->getSignalement();
        $visites = $interventionRepository->getOrderedVisitesForSignalement($signalement);
        $infoDesordres = $signalementDesordresProcessor->process($signalement);
        $allPhotosOrdered = PhotoHelper::getSortedPhotos($signalement);
        $linkToVisitGrid = false;
        $existingVisitGrid = $fileRepository->findOneBy([
            'territory' => $signalement->getAddress()->getTerritory(),
            'documentType' => DocumentType::GRILLE_DE_VISITE,
        ]);
        if ($existingVisitGrid) {
            $linkToVisitGrid = $urlGenerator->generate('show_file', ['uuid' => $existingVisitGrid->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $htmlTargetContents = [
            [
                'target' => '#bloc-visites',
                'content' => $this->renderView('back/signalement/view/visites/bloc-visites.html.twig',
                    [
                        'signalement' => $signalement,
                        'visites' => $visites,
                        'criteres' => $infoDesordres['criteres'],
                        'linkToVisitGrid' => $linkToVisitGrid,
                    ]),
            ],
            [
                'target' => '#list-suivis',
                'content' => $this->renderView('back/signalement/view/suivis.html.twig',
                    ['signalement' => $signalement]
                ),
            ],
            [
                'target' => '#photos-album',
                'content' => $this->renderView('back/signalement/view/photos-album.html.twig',
                    [
                        'signalement' => $signalement,
                        'allPhotosOrdered' => $allPhotosOrdered,
                    ]
                ),
            ],
        ];
        // TODO : Refonte visites - On ne devrait plus avoir besoin de toutes ces fonctions
        $functions = [
            [
                'name' => 'reloadTinyMCE',
                'args' => ['textarea.editor'],
            ],
            [
                'name' => 'attachAjaxFormHandlers',
            ],
            [
                'name' => 'initSearchCheckboxWidgets',
            ],
            [
                'name' => 'applyFilter',
            ],
            [
                'name' => 'openPhotoAlbumAddEventListeners',
            ],
            [
                'name' => 'btnSignalementFileEditAddEventListeners',
            ],
            [
                'name' => 'btnSignalementFileDeleteAddEventListeners',
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
    #[IsGranted(SignalementVoter::SIGN_ADD_VISITE, subject: 'signalement')]
    public function addVisite(
        Signalement $signalement,
        Request $request,
    ): Response {
        $intervention = (new Intervention())->setSignalement($signalement);
        $addVisiteForm = $this->generateUrl('back_signalement_visite_add', ['uuid' => $signalement->getUuid()]);
        $addVisiteForm = $this->createForm(AddAndRescheduleVisiteType::class, $intervention, options: ['action' => $addVisiteForm]);

        $addVisiteForm->handleRequest($request);

        if (!$addVisiteForm->isSubmitted() || !$addVisiteForm->isValid()) {
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $addVisiteForm, withPrefix: true)];

            return $this->json($response, $response['code']);
        }
        // TODO
        $flashMessages = [];
        $flashMessages[] = ['type' => 'success', 'message' => 'La visite a été ajoutée avec succès.'];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true]);
    }

    #[Route('/{uuid:signalement}/v1/visites/ajouter', name: 'back_signalement_visite_add_v1', methods: 'POST')]
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
        SignalementDesordresProcessor $signalementDesordresProcessor,
        FileRepository $fileRepository,
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_ADD_VISITE, $signalement);

        $errorRedirect = $this->getSecurityResponse(
            $request,
            'signalement_add_visit_'.$signalement->getId(),
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        [$success, $fileNameOrError] = $this->getUploadedFile($request, 'visite-add', $uploadHandler, $filenameGenerator);
        if (!$success) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => $fileNameOrError];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true]);
        }

        $fileName = $fileNameOrError;

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
        $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getAddress()->getTerritory());
        $errorMessage = $this->validateRequest($visiteRequest, $validator);
        if ($errorMessage) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => \sprintf("Erreurs lors de l'enregistrement de la visite : %s, veuillez réessayer.", $errorMessage)];
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
                        $user->getPartnerInTerritoryOrFirstOne($signalement->getAddress()->getTerritory())
                    ),
                    InterventionCreatedEvent::NAME
                );
                $entityManager->flush();
            }

            return $this->buildVisitesAjaxResponse(
                intervention: $intervention,
                interventionRepository: $interventionRepository,
                signalementDesordresProcessor: $signalementDesordresProcessor,
                fileRepository: $fileRepository,
                urlGenerator: $urlGenerator,
                flashMessages: $flashMessages,
            );
        } else {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => 'Erreur lors de l\'enregistrement de la visite, veuillez réessayer.'];
        }

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true]);
    }

    #[Route('/{id}/visites/annuler', name: 'back_signalement_visite_cancel')]
    #[IsGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, subject: 'intervention')]
    public function cancelVisite(
        Intervention $intervention,
        Request $request,
    ): Response {
        // TODO : bloquer pour les statut != PLANNED (permet d'intégrer les VISITE_CONTROLE / ARRETE_PREFECTORAL) - faire un voter dédié

        $cancelVisiteRoute = $this->generateUrl('back_signalement_visite_cancel', ['id' => $intervention->getId()]);
        $cancelVisiteForm = $this->createForm(CancelVisiteType::class, $intervention, options: ['action' => $cancelVisiteRoute]);

        $title = 'Annuler la visite du '.($intervention->getScheduledAt()->format('H') > 0 ? $intervention->getScheduledAt()->format('d/m/Y à H:i') : $intervention->getScheduledAt()->format('d/m/Y'));
        $html = $this->renderView('back/signalement/view/visites/_cancel-visite-form.html.twig', [
            'cancelVisiteForm' => $cancelVisiteForm,
        ]);

        return $this->json(['content' => $html, 'title' => $title]);
    }

    #[Route('/{uuid:signalement}/v1/visites/annuler', name: 'back_signalement_visite_cancel_v1', methods: 'POST')]
    public function cancelVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
        InterventionRepository $interventionRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        FileRepository $fileRepository,
        UrlGeneratorInterface $urlGenerator,
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
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => 'Cette visite est déja passée et ne peut pas être annulée, merci de la noter comme non-effectuée.'];

            return $this->buildVisitesAjaxResponse(
                intervention: $intervention,
                interventionRepository: $interventionRepository,
                signalementDesordresProcessor: $signalementDesordresProcessor,
                fileRepository: $fileRepository,
                urlGenerator: $urlGenerator,
                flashMessages: $flashMessages,
            );
        }

        $errorRedirect = $this->getSecurityResponse(
            $request,
            'signalement_cancel_visit_'.$requestCancelData['intervention'],
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
        if ($interventionManager->cancelVisiteFromRequest($visiteRequest, $user->getPartnerInTerritory($signalement->getAddress()->getTerritory()))) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Visite annulée', 'message' => 'La visite a bien été annulée.'];
        } else {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => 'Erreur lors de l\'annulation de la visite.'];
        }

        return $this->buildVisitesAjaxResponse(
            intervention: $intervention,
            interventionRepository: $interventionRepository,
            signalementDesordresProcessor: $signalementDesordresProcessor,
            fileRepository: $fileRepository,
            urlGenerator: $urlGenerator,
            flashMessages: $flashMessages,
        );
    }

    /**
     * @throws \Exception
     */
    #[Route('/{id}/visites/reprogrammer', name: 'back_signalement_visite_reschedule')]
    #[IsGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, subject: 'intervention')]
    public function rescheduleVisite(
        Intervention $intervention,
        Request $request,
    ): Response {
        // TODO : bloquer pour les statut != PLANNED (permet d'intégrer les VISITE_CONTROLE / ARRETE_PREFECTORAL)  - faire un voter dédié

        $recheduleVisiteRoute = $this->generateUrl('back_signalement_visite_reschedule', ['id' => $intervention->getId()]);
        $rescheduleVisiteForm = $this->createForm(AddAndRescheduleVisiteType::class, $intervention, options: ['action' => $recheduleVisiteRoute]);

        $title = 'Modifier la visite du '.($intervention->getScheduledAt()->format('H') > 0 ? $intervention->getScheduledAt()->format('d/m/Y à H:i') : $intervention->getScheduledAt()->format('d/m/Y'));
        $html = $this->renderView('back/signalement/view/visites/_reschedule-visite-form.html.twig', [
            'rescheduleVisiteForm' => $rescheduleVisiteForm,
            'intervention' => $intervention,
        ]);

        return $this->json(['content' => $html, 'title' => $title]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/{uuid:signalement}/v1/visites/reprogrammer', name: 'back_signalement_visite_reschedule_v1', methods: 'POST')]
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
        SignalementDesordresProcessor $signalementDesordresProcessor,
        FileRepository $fileRepository,
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
    ): Response {
        $requestData = $request->request->all();
        $requestRescheduleData = RequestDataExtractor::getArray($requestData, 'visite-reschedule');

        $intervention = $interventionRepository->findOneBy(['id' => $requestRescheduleData['intervention'], 'signalement' => $signalement]);
        if (!$intervention) {
            $this->addFlash('error', "Cette visite n'existe pas.");

            return $this->redirectToRoute('back_signalements_index');
        }
        $this->denyAccessUnlessGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, $intervention);

        $errorRedirect = $this->getSecurityResponse(
            $request,
            'signalement_reschedule_visit_'.$requestRescheduleData['intervention'],
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $previousDate = $intervention->getScheduledAt();
        [$success, $fileNameOrError] = $this->getUploadedFile($request, 'visite-reschedule', $uploadHandler, $filenameGenerator);
        if (!$success) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => $fileNameOrError];

            return $this->buildVisitesAjaxResponse(
                intervention: $intervention,
                interventionRepository: $interventionRepository,
                signalementDesordresProcessor: $signalementDesordresProcessor,
                fileRepository: $fileRepository,
                urlGenerator: $urlGenerator,
                flashMessages: $flashMessages,
            );
        }

        $fileName = $fileNameOrError;

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
        $partner = $user->getPartnerInTerritory($signalement->getAddress()->getTerritory());
        $errorMessage = $this->validateRequest($visiteRequest, $validator);
        if ($errorMessage) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => \sprintf('Erreurs lors de la modification de la visite : %s, veuillez réessayer.', $errorMessage)];
        } elseif ($intervention = $interventionManager->rescheduleVisiteFromRequest($signalement, $visiteRequest, $partner)) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => self::SUCCESS_MSG_CONFIRM];
            if ($intervention->getScheduledAt()->format('Y-m-d') > (new \DateTimeImmutable())->format('Y-m-d')) {
                $eventDispatcher->dispatch(
                    new InterventionRescheduledEvent(
                        $intervention,
                        $user,
                        $previousDate,
                        $partner
                    ), InterventionRescheduledEvent::NAME
                );
                $entityManager->flush();
            }
        } else {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => 'Erreur lors de la modification de la visite, veuillez réessayer.'];
        }

        return $this->buildVisitesAjaxResponse(
            intervention: $intervention,
            interventionRepository: $interventionRepository,
            signalementDesordresProcessor: $signalementDesordresProcessor,
            fileRepository: $fileRepository,
            urlGenerator: $urlGenerator,
            flashMessages: $flashMessages,
        );
    }

    /**
     * @throws \Exception
     */
    #[Route('/{id}/visites/confirmer', name: 'back_signalement_visite_confirm')]
    #[IsGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, subject: 'intervention')]
    public function confirmVisite(
        Intervention $intervention,
        Request $request,
    ): Response {
        // TODO : bloquer pour les statut != PLANNED (permet d'intégrer les VISITE_CONTROLE / ARRETE_PREFECTORAL)  - faire un voter dédié

        $confirmVisiteRoute = $this->generateUrl('back_signalement_visite_confirm', ['id' => $intervention->getId()]);
        $confirmVisiteForm = $this->createForm(ConfirmVisiteType::class, $intervention, options: ['action' => $confirmVisiteRoute]);

        $title = 'Conclusion de la visite du '.($intervention->getScheduledAt()->format('H') > 0 ? $intervention->getScheduledAt()->format('d/m/Y à H:i') : $intervention->getScheduledAt()->format('d/m/Y'));
        $html = $this->renderView('back/signalement/view/visites/_confirm-visite-form.html.twig', [
            'confirmVisiteForm' => $confirmVisiteForm,
            'intervention' => $intervention,
        ]);

        return $this->json(['content' => $html, 'title' => $title]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/{uuid:signalement}/v1/visites/confirmer', name: 'back_signalement_visite_confirm_v1', methods: 'POST')]
    public function confirmVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
        InterventionRepository $interventionRepository,
        UploadHandlerService $uploadHandler,
        FilenameGenerator $filenameGenerator,
        ValidatorInterface $validator,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        FileRepository $fileRepository,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $requestData = $request->request->all();
        $requestConfirmData = RequestDataExtractor::getArray($requestData, 'visite-confirm');

        $intervention = $interventionRepository->findOneBy(['id' => $requestConfirmData['intervention'], 'signalement' => $signalement]);
        if (!$intervention) {
            $this->addFlash('error', "Cette visite n'existe pas.");

            return $this->redirectToRoute('back_signalements_index');
        }
        $this->denyAccessUnlessGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, $intervention);

        $errorRedirect = $this->getSecurityResponse(
            $request,
            'signalement_confirm_visit_'.$requestConfirmData['intervention'],
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        [$success, $fileNameOrError] = $this->getUploadedFile($request, 'visite-confirm', $uploadHandler, $filenameGenerator);
        if (!$success) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => $fileNameOrError];

            return $this->buildVisitesAjaxResponse(
                intervention: $intervention,
                interventionRepository: $interventionRepository,
                signalementDesordresProcessor: $signalementDesordresProcessor,
                fileRepository: $fileRepository,
                urlGenerator: $urlGenerator,
                flashMessages: $flashMessages,
                closeModalAndReload: false,
            );
        }

        $fileName = $fileNameOrError;

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
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => \sprintf('Erreurs lors de la conclusion de la visite : %s, veuillez réessayer.', $errorMessage)];
        }
        if ($interventionManager->confirmVisiteFromRequest($visiteRequest, $user->getPartnerInTerritory($signalement->getAddress()->getTerritory()))) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => self::SUCCESS_MSG_CONFIRM];

            return $this->buildVisitesAjaxResponse(
                intervention: $intervention,
                interventionRepository: $interventionRepository,
                signalementDesordresProcessor: $signalementDesordresProcessor,
                fileRepository: $fileRepository,
                urlGenerator: $urlGenerator,
                flashMessages: $flashMessages,
            );
        }
        $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => 'Erreur lors de la conclusion de la visite, veuillez réessayer.'];

        return $this->buildVisitesAjaxResponse(
            intervention: $intervention,
            interventionRepository: $interventionRepository,
            signalementDesordresProcessor: $signalementDesordresProcessor,
            fileRepository: $fileRepository,
            urlGenerator: $urlGenerator,
            flashMessages: $flashMessages,
            closeModalAndReload: false,
        );
    }

    #[Route('/{id}/visites/editer-conclusion', name: 'back_signalement_visite_edit_conclusion')]
    #[IsGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, subject: 'intervention')]
    public function editConclusionVisite(
        Intervention $intervention,
        Request $request,
    ): Response {
        // TODO : bloquer pour les statut != PLANNED (permet d'intégrer les VISITE_CONTROLE / ARRETE_PREFECTORAL) - faire un voter dédié

        $editConclusionVisiteRoute = $this->generateUrl('back_signalement_visite_edit_conclusion', ['id' => $intervention->getId()]);
        $editConclusionVisiteForm = $this->createForm(EditConclusionVisiteType::class, $intervention, options: ['action' => $editConclusionVisiteRoute]);

        $title = 'Edition de la visite du '.($intervention->getScheduledAt()->format('H') > 0 ? $intervention->getScheduledAt()->format('d/m/Y à H:i') : $intervention->getScheduledAt()->format('d/m/Y'));
        $html = $this->renderView('back/signalement/view/visites/_edit-conclusion-visite-form.html.twig', [
            'editConclusionVisiteForm' => $editConclusionVisiteForm,
            'intervention' => $intervention,
        ]);

        return $this->json(['content' => $html, 'title' => $title]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/{uuid:signalement}/v1/visites/editer', name: 'back_signalement_visite_edit_v1', methods: 'POST')]
    public function editVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
        InterventionRepository $interventionRepository,
        UploadHandlerService $uploadHandler,
        EventDispatcherInterface $eventDispatcher,
        FilenameGenerator $filenameGenerator,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        FileRepository $fileRepository,
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
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

        $errorRedirect = $this->getSecurityResponse(
            $request,
            'signalement_edit_visit_'.$requestEditData['intervention'],
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }
        [$success, $fileNameOrError] = $this->getUploadedFile($request, 'visite-edit', $uploadHandler, $filenameGenerator);
        if (!$success) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => $fileNameOrError];

            return $this->buildVisitesAjaxResponse(
                intervention: $intervention,
                interventionRepository: $interventionRepository,
                signalementDesordresProcessor: $signalementDesordresProcessor,
                fileRepository: $fileRepository,
                urlGenerator: $urlGenerator,
                flashMessages: $flashMessages,
                closeModalAndReload: false,
            );
        }

        $fileName = $fileNameOrError;
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
        $partner = $user->getPartnerInTerritory($signalement->getAddress()->getTerritory());

        if ($interventionManager->editVisiteFromRequest($visiteRequest, $partner)) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => self::SUCCESS_MSG_CONFIRM];
            $eventDispatcher->dispatch(new InterventionEditedEvent(
                $intervention,
                $user,
                $visiteRequest->isUsagerNotified(),
                $user->getPartnerInTerritoryOrFirstOne($signalement->getAddress()->getTerritory())
            ), InterventionEditedEvent::NAME);
            $entityManager->flush();

            return $this->buildVisitesAjaxResponse(
                intervention: $intervention,
                interventionRepository: $interventionRepository,
                signalementDesordresProcessor: $signalementDesordresProcessor,
                fileRepository: $fileRepository,
                urlGenerator: $urlGenerator,
                flashMessages: $flashMessages,
            );
        }
        $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => 'Erreur lors de la modification de la visite, veuillez réessayer.'];

        return $this->buildVisitesAjaxResponse(
            intervention: $intervention,
            interventionRepository: $interventionRepository,
            signalementDesordresProcessor: $signalementDesordresProcessor,
            fileRepository: $fileRepository,
            urlGenerator: $urlGenerator,
            flashMessages: $flashMessages,
            closeModalAndReload: false,
        );
    }

    #[Route('/visites/{intervention}/delete-rapport', name: 'back_signalement_visite_deleterapport')]
    public function deleteRapportVisiteFromSignalement(
        Intervention $intervention,
        Request $request,
        EntityManagerInterface $entityManager,
        UploadHandlerService $uploadHandlerService,
        InterventionRepository $interventionRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        FileRepository $fileRepository,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        if (!$intervention->getRapportDeVisite()) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => "Ce rapport n'existe pas."];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        $this->denyAccessUnlessGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, $intervention);
        $errorRedirect = $this->getSecurityResponse(
            $request,
            'delete_rapport',
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $file = $intervention->getRapportDeVisite();
        $uploadHandlerService->deleteFileInBucket($file);
        $entityManager->remove($file);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Document supprimé', 'message' => 'Le rapport de visite a bien été supprimé.'];

        return $this->buildVisitesAjaxResponse(
            intervention: $intervention,
            interventionRepository: $interventionRepository,
            signalementDesordresProcessor: $signalementDesordresProcessor,
            fileRepository: $fileRepository,
            urlGenerator: $urlGenerator,
            flashMessages: $flashMessages,
        );
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
