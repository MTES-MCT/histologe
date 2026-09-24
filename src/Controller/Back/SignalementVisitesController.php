<?php

namespace App\Controller\Back;

use App\Entity\Enum\DocumentType;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
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
use App\Service\Signalement\PhotoHelper;
use App\Service\Signalement\SignalementDesordresProcessor;
use App\Service\UploadHandlerService;
use App\Utils\FormHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/bo/signalements')]
class SignalementVisitesController extends AbstractController
{
    private const string SUCCESS_MSG_ADD = 'La date de visite a bien été définie.';
    private const string SUCCESS_MSG_CONFIRM = 'Les informations de la visite ont bien été enregistrées.';

    public function __construct(
        private readonly UploadHandlerService $uploadHandler,
        private readonly FilenameGenerator $filenameGenerator,
        private readonly InterventionRepository $interventionRepository,
        private readonly SignalementDesordresProcessor $signalementDesordresProcessor,
        private readonly FileRepository $fileRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Target('interventionPlanningStateMachine')]
        private readonly WorkflowInterface $interventionPlanningStateMachine,
        private readonly EntityManagerInterface $entityManager,
        private readonly InterventionManager $interventionManager,
    ) {
    }

    /**
     * @return array{0: bool, 1: ?string}
     */
    private function getUploadedFile(?UploadedFile $uploadedFile): array
    {
        if (null === $uploadedFile) {
            return [true, null];
        }

        $newFilename = $this->filenameGenerator->generate($uploadedFile);
        try {
            $uploadedFilename = $this->uploadHandler->uploadFromFile($uploadedFile, $newFilename);

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
        array $flashMessages,
        bool $closeModalAndReload = true,
    ): Response {
        $signalement = $intervention->getSignalement();
        $visites = $this->interventionRepository->getOrderedVisitesForSignalement($signalement);
        $infoDesordres = $this->signalementDesordresProcessor->process($signalement);
        $allPhotosOrdered = PhotoHelper::getSortedPhotos($signalement);
        $linkToVisitGrid = false;
        $existingVisitGrid = $this->fileRepository->findOneBy([
            'territory' => $signalement->getAddress()->getTerritory(),
            'documentType' => DocumentType::GRILLE_DE_VISITE,
        ]);
        if ($existingVisitGrid) {
            $linkToVisitGrid = $this->urlGenerator->generate('show_file', ['uuid' => $existingVisitGrid->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);
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
        $functions = [
            ['name' => 'applyFilter'],
            ['name' => 'openPhotoAlbumAddEventListeners'],
            ['name' => 'btnSignalementFileEditAddEventListeners'],
            ['name' => 'btnSignalementFileDeleteAddEventListeners'],
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
        $file = $addVisiteForm->get('rapportDeVisite')->getData();
        [$success, $fileNameOrError] = $this->getUploadedFile($file);
        if (!$success) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => $fileNameOrError];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true]);
        }

        $this->interventionManager->updateVisiteFromData(
            intervention: $intervention,
            scheduledAt: $addVisiteForm->get('scheduledAt')->getData(),
            scheduledAtTime: $addVisiteForm->get('scheduledAtTime')->getData(),
            partnerChoice: $addVisiteForm->get('partnerChoice')->getData(),
            visiteDone: $addVisiteForm->get('visiteDone')->getData(),
            fileName: $fileNameOrError,
        );

        $todayDate = new \DateTimeImmutable();
        if ($intervention->getScheduledAt()->format('Y-m-d') <= $todayDate->format('Y-m-d')) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Visite ajoutée', 'message' => self::SUCCESS_MSG_CONFIRM];
        } else {
            $flashMessages[] = ['type' => 'success', 'title' => 'Visite ajoutée', 'message' => self::SUCCESS_MSG_ADD];
        }

        $this->entityManager->persist($intervention);
        $this->entityManager->flush();

        return $this->buildVisitesAjaxResponse(intervention: $intervention, flashMessages: $flashMessages);
    }

    #[Route('/{id}/visites/annuler', name: 'back_signalement_visite_cancel')]
    #[IsGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, subject: 'intervention')]
    public function cancelVisite(
        Intervention $intervention,
        Request $request,
    ): Response {
        if (!$this->interventionPlanningStateMachine->can($intervention, 'cancel')) {
            throw $this->createAccessDeniedException();
        }

        $cancelVisiteRoute = $this->generateUrl('back_signalement_visite_cancel', ['id' => $intervention->getId()]);
        $cancelVisiteForm = $this->createForm(CancelVisiteType::class, $intervention, options: ['action' => $cancelVisiteRoute]);

        $cancelVisiteForm->handleRequest($request);

        if ($cancelVisiteForm->isSubmitted() && !$cancelVisiteForm->isValid()) {
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $cancelVisiteForm, withPrefix: true)];

            return $this->json($response, $response['code']);
        }
        if ($cancelVisiteForm->isSubmitted() && $cancelVisiteForm->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $context['createdByPartner'] = $user->getPartnerInTerritory($intervention->getSignalement()->getAddress()->getTerritory());
            $this->interventionPlanningStateMachine->apply($intervention, 'cancel', $context);
            $this->entityManager->flush();
            $flashMessages[] = ['type' => 'success', 'title' => 'Visite annulée', 'message' => 'La visite a bien été annulée.'];

            return $this->buildVisitesAjaxResponse(intervention: $intervention, flashMessages: $flashMessages);
        }

        $title = 'Annuler la visite du '.($intervention->getScheduledAt()->format('H') > 0 ? $intervention->getScheduledAt()->format('d/m/Y à H:i') : $intervention->getScheduledAt()->format('d/m/Y'));
        $html = $this->renderView('back/signalement/view/visites/_cancel-visite-form.html.twig', [
            'cancelVisiteForm' => $cancelVisiteForm,
        ]);

        return $this->json(['content' => $html, 'title' => $title]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/{id}/visites/reprogrammer', name: 'back_signalement_visite_reschedule')]
    #[IsGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, subject: 'intervention')]
    public function rescheduleVisite(
        Intervention $intervention,
        Request $request,
        FormFactoryInterface $formFactory,
    ): Response {
        if (Intervention::STATUS_PLANNED !== $intervention->getStatus()) {
            throw $this->createAccessDeniedException();
        }

        $recheduleVisiteRoute = $this->generateUrl('back_signalement_visite_reschedule', ['id' => $intervention->getId()]);
        $rescheduleVisiteForm = $formFactory->createNamed('reschedule_visite', AddAndRescheduleVisiteType::class, $intervention, ['action' => $recheduleVisiteRoute]);

        $rescheduleVisiteForm->handleRequest($request);

        if ($rescheduleVisiteForm->isSubmitted() && !$rescheduleVisiteForm->isValid()) {
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $rescheduleVisiteForm, withPrefix: true)];

            return $this->json($response, $response['code']);
        }
        if ($rescheduleVisiteForm->isSubmitted()) {
            $file = $rescheduleVisiteForm->get('rapportDeVisite')->getData();
            [$success, $fileNameOrError] = $this->getUploadedFile($file);
            if (!$success) {
                $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => $fileNameOrError];

                return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true]);
            }
        }
        if ($rescheduleVisiteForm->isSubmitted() && $rescheduleVisiteForm->isValid()) {
            $this->interventionManager->updateVisiteFromData(
                intervention: $intervention,
                scheduledAt: $rescheduleVisiteForm->get('scheduledAt')->getData(),
                scheduledAtTime: $rescheduleVisiteForm->get('scheduledAtTime')->getData(),
                partnerChoice: $rescheduleVisiteForm->get('partnerChoice')->getData(),
                visiteDone: $rescheduleVisiteForm->get('visiteDone')->getData(),
                fileName: $fileNameOrError,
                eventType: 'reschedule',
            );
            $this->entityManager->flush();
            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => self::SUCCESS_MSG_CONFIRM];

            return $this->buildVisitesAjaxResponse(intervention: $intervention, flashMessages: $flashMessages);
        }

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
    #[Route('/{id}/visites/confirmer', name: 'back_signalement_visite_confirm')]
    #[IsGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, subject: 'intervention')]
    public function confirmVisite(
        Intervention $intervention,
        Request $request,
    ): Response {
        if (!$this->interventionPlanningStateMachine->can($intervention, 'confirm')) {
            throw $this->createAccessDeniedException();
        }

        $confirmVisiteRoute = $this->generateUrl('back_signalement_visite_confirm', ['id' => $intervention->getId()]);
        $confirmVisiteForm = $this->createForm(ConfirmVisiteType::class, $intervention, options: ['action' => $confirmVisiteRoute]);

        $confirmVisiteForm->handleRequest($request);

        if ($confirmVisiteForm->isSubmitted() && !$confirmVisiteForm->isValid()) {
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $confirmVisiteForm, withPrefix: true)];

            return $this->json($response, $response['code']);
        }
        if ($confirmVisiteForm->isSubmitted()) {
            $file = $confirmVisiteForm->get('rapportDeVisite')->getData();
            [$success, $fileNameOrError] = $this->getUploadedFile($file);
            if (!$success) {
                $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => $fileNameOrError];

                return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true]);
            }
        }
        if ($confirmVisiteForm->isSubmitted() && $confirmVisiteForm->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $createdByPartner = $user->getPartnerInTerritory($intervention->getSignalement()->getAddress()->getTerritory());
            $this->interventionManager->confirmOrAbortVisiteFromData($intervention, $createdByPartner, $confirmVisiteForm->get('visiteDone')->getData(), $fileNameOrError);
            $this->entityManager->flush();
            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => self::SUCCESS_MSG_CONFIRM];

            return $this->buildVisitesAjaxResponse(intervention: $intervention, flashMessages: $flashMessages);
        }

        $title = 'Conclusion de la visite du '.($intervention->getScheduledAt()->format('H') > 0 ? $intervention->getScheduledAt()->format('d/m/Y à H:i') : $intervention->getScheduledAt()->format('d/m/Y'));
        $html = $this->renderView('back/signalement/view/visites/_confirm-visite-form.html.twig', [
            'confirmVisiteForm' => $confirmVisiteForm,
            'intervention' => $intervention,
        ]);

        return $this->json(['content' => $html, 'title' => $title]);
    }

    #[Route('/{id}/visites/editer-conclusion', name: 'back_signalement_visite_edit_conclusion')]
    #[IsGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, subject: 'intervention')]
    public function editConclusionVisite(
        Intervention $intervention,
        Request $request,
    ): Response {
        if (Intervention::STATUS_DONE !== $intervention->getStatus()) {
            throw $this->createAccessDeniedException();
        }

        $editConclusionVisiteRoute = $this->generateUrl('back_signalement_visite_edit_conclusion', ['id' => $intervention->getId()]);
        $editConclusionVisiteForm = $this->createForm(EditConclusionVisiteType::class, $intervention, options: ['action' => $editConclusionVisiteRoute]);

        $editConclusionVisiteForm->handleRequest($request);

        if ($editConclusionVisiteForm->isSubmitted() && !$editConclusionVisiteForm->isValid()) {
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $editConclusionVisiteForm, withPrefix: true)];

            return $this->json($response, $response['code']);
        }
        if ($editConclusionVisiteForm->isSubmitted()) {
            $file = $editConclusionVisiteForm->get('rapportDeVisite')->getData();
            [$success, $fileNameOrError] = $this->getUploadedFile($file);
            if (!$success) {
                $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => $fileNameOrError];

                return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true]);
            }
        }
        if ($editConclusionVisiteForm->isSubmitted() && $editConclusionVisiteForm->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $createdByPartner = $user->getPartnerInTerritoryOrFirstOne($intervention->getSignalement()->getAddress()->getTerritory());
            $this->interventionManager->editConclusionVisiteFromRequest($intervention, $createdByPartner, $fileNameOrError);

            $this->entityManager->flush();
            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => self::SUCCESS_MSG_CONFIRM];

            return $this->buildVisitesAjaxResponse(intervention: $intervention, flashMessages: $flashMessages);
        }

        $title = 'Edition de la visite du '.($intervention->getScheduledAt()->format('H') > 0 ? $intervention->getScheduledAt()->format('d/m/Y à H:i') : $intervention->getScheduledAt()->format('d/m/Y'));
        $html = $this->renderView('back/signalement/view/visites/_edit-conclusion-visite-form.html.twig', [
            'editConclusionVisiteForm' => $editConclusionVisiteForm,
            'intervention' => $intervention,
        ]);

        return $this->json(['content' => $html, 'title' => $title]);
    }

    #[Route('/visites/{intervention}/delete-rapport', name: 'back_signalement_visite_deleterapport')]
    public function deleteRapportVisiteFromSignalement(
        Intervention $intervention,
    ): Response {
        if (!$intervention->getRapportDeVisite()) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => "Ce rapport n'existe pas."];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        $this->denyAccessUnlessGranted(InterventionVoter::INTERVENTION_EDIT_VISITE, $intervention);

        $file = $intervention->getRapportDeVisite();
        $this->uploadHandler->deleteFileInBucket($file);
        $this->entityManager->remove($file);
        $this->entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Document supprimé', 'message' => 'Le rapport de visite a bien été supprimé.'];

        return $this->buildVisitesAjaxResponse(intervention: $intervention, flashMessages: $flashMessages);
    }
}
