<?php

namespace App\Controller;

use App\Dto\DemandeLienSignalement;
use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\File;
use App\Entity\Model\InformationProcedure;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Entity\Suivi;
use App\Entity\User;
use App\Event\SuiviViewedEvent;
use App\Form\CoordonneesBailleurType;
use App\Form\DemandeLienSignalementType;
use App\Form\MessageUsagerType;
use App\Form\UsagerBasculeProcedureType;
use App\Form\UsagerCancelProcedureType;
use App\Form\UsagerCoordonneesTiersType;
use App\Form\UsagerPoursuivreProcedureType;
use App\Manager\SignalementDraftManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\CommuneRepository;
use App\Repository\FileRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Security\User\SignalementUser;
use App\Security\Voter\SignalementFoVoter;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\HtmlCleaner;
use App\Service\ImageManipulationHandler;
use App\Service\InjonctionBailleur\InjonctionBailleurService;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Security\FileScanner;
use App\Service\Signalement\AutoAssigner;
use App\Service\Signalement\PostalCodeHomeChecker;
use App\Service\Signalement\SignalementDesordresProcessor;
use App\Service\Signalement\SignalementDuplicateChecker;
use App\Service\Signalement\SuiviSeenMarker;
use App\Service\SuiviCategoryMapper;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/')]
class SignalementController extends AbstractController
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    #[Route(
        '/signalement',
        name: 'front_signalement',
        defaults: ['show_sitemap' => true]
    )]
    public function index(
        Request $request,
    ): Response {
        return $this->render('front/formulaire_signalement.html.twig', [
            'uuid_signalement' => null,
            'profile' => $request->query->get('profil'),
        ]);
    }

    #[Route('/signalement-draft/{uuid:signalementDraft}', name: 'front_formulaire_signalement_edit', methods: 'GET')]
    public function edit(
        SignalementDraft $signalementDraft,
    ): Response {
        if (SignalementDraftStatus::EN_COURS !== $signalementDraft->getStatus()) {
            $this->addFlash('error', 'Le brouillon n\'est plus modifiable.');

            return $this->redirectToRoute('front_signalement');
        }

        return $this->render('front/formulaire_signalement.html.twig', [
            'uuid_signalement' => $signalementDraft->getUuid(),
            'profile' => '',
        ]);
    }

    #[Route('/signalement-draft/envoi', name: 'envoi_formulaire_signalement_draft', methods: 'POST')]
    public function sendSignalementDraft(
        Request $request,
        SignalementDraftRequestSerializer $serializer,
        SignalementDraftManager $signalementDraftManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->deserialize(
            $payload = $request->getContent(),
            SignalementDraftRequest::class,
            'json'
        );
        $errors = $validator->validate(
            $signalementDraftRequest,
            null,
            ['Default', 'POST_'.strtoupper($signalementDraftRequest->getProfil())]
        );
        if (0 === $errors->count()) {
            return $this->json([
                'uuid' => $signalementDraftManager->create(
                    $signalementDraftRequest,
                    json_decode($payload, true)
                ),
            ]);
        }

        return $this->json($errors);
    }

    #[Route('/signalement-draft/check', name: 'check_signalement_or_draft_already_exists', methods: 'POST')]
    public function checkSignalementOrDraftAlreadyExists(
        Request $request,
        SignalementDraftRequestSerializer $serializer,
        ValidatorInterface $validator,
        SignalementDuplicateChecker $signalementDuplicateChecker,
        SignalementDraftManager $signalementDraftManager,
    ): JsonResponse {
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->deserialize(
            $payload = $request->getContent(),
            SignalementDraftRequest::class,
            'json'
        );

        if (empty($signalementDraftRequest->getProfil())) {
            $violation = new ConstraintViolation(
                'Merci de sélectionner le type de déclarant',
                null,
                [],
                $signalementDraftRequest,
                'profil',
                null
            );
            $errors = new ConstraintViolationList([$violation]);
        } else {
            $errors = $validator->validate(
                $signalementDraftRequest,
                null,
                ['Default', 'POST_'.strtoupper($signalementDraftRequest->getProfil())]
            );
        }

        if (0 === $errors->count()) {
            $result = $signalementDuplicateChecker->check($signalementDraftRequest);
            if (!empty($result['uuid']) && 'waiting_creation' === $result['uuid']) {
                $result['uuid'] = $signalementDraftManager->create(
                    $signalementDraftRequest,
                    json_decode($payload, true)
                );
            }

            return $this->json($result);
        }

        return $this->json($errors);
    }

    #[Route('/signalement-draft/{uuid:signalementDraft}/envoi', name: 'mise_a_jour_formulaire_signalement_draft', methods: 'PUT')]
    public function updateSignalementDraft(
        Request $request,
        SignalementDraftRequestSerializer $serializer,
        SignalementDraftManager $signalementDraftManager,
        ValidatorInterface $validator,
        SignalementDraft $signalementDraft,
    ): JsonResponse {
        if (SignalementDraftStatus::ARCHIVE === $signalementDraft->getStatus()) {
            throw $this->createNotFoundException();
        }
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->deserialize(
            $payload = $request->getContent(),
            SignalementDraftRequest::class,
            'json'
        );

        $groupValidation = ['Default', 'POST_'.strtoupper($signalementDraftRequest->getProfil())];
        if ('validation_signalement' === $signalementDraftRequest->getCurrentStep()) {
            $groupValidation[] = 'PUT_'.strtoupper($signalementDraftRequest->getProfil());
        }
        $errors = $validator->validate($signalementDraftRequest, null, $groupValidation);

        if (0 === $errors->count()) {
            $result = $signalementDraftManager->update(
                $signalementDraft,
                $signalementDraftRequest,
                json_decode($payload, true)
            );

            return $this->json($result);
        }

        return $this->json($errors);
    }

    #[Route('/signalement-draft/{uuid:signalementDraft}/informations', name: 'informations_signalement_draft', methods: 'GET')]
    public function getSignalementDraft(
        SignalementDraft $signalementDraft,
    ): JsonResponse {
        return $this->json([
            'signalement' => SignalementDraftStatus::EN_COURS === $signalementDraft->getStatus()
                ? $signalementDraft :
                null,
        ]);
    }

    #[Route('/signalement-draft/send_mail', name: 'send_mail_continue_from_draft', methods: 'POST')]
    public function sendMailContinueFromDraft(
        NotificationMailerRegistry $notificationMailerRegistry,
        SignalementDraftRequestSerializer $serializer,
        Request $request,
        SignalementDraftManager $signalementDraftManager,
    ): JsonResponse {
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->deserialize(
            $payload = $request->getContent(),
            SignalementDraftRequest::class,
            'json'
        );

        $signalementDraft = $signalementDraftManager->findSignalementDraftByAddressAndMail(
            $signalementDraftRequest,
        );

        if ($signalementDraft) {
            $success = $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CONTINUE_FROM_DRAFT_TO_USAGER,
                    to: $signalementDraft->getEmailDeclarant(),
                    signalementDraft: $signalementDraft,
                )
            );
            if ($success) {
                return $this->json(['success' => true]);
            }

            return $this->json([
                'success' => false,
                'label' => 'Erreur',
                'message' => 'L\'envoi du mail n\'a pas fonctionné, veuillez réessayer ou faire un nouveau signalement.',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['response' => 'error'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/signalement/{uuid:signalement}/send_mail_get_lien_suivi', name: 'send_mail_get_lien_suivi')]
    public function sendMailGetLienSuivi(
        NotificationMailerRegistry $notificationMailerRegistry,
        Signalement $signalement,
        Request $request,
    ): JsonResponse|RedirectResponse {
        if ($request->isMethod('POST')) {
            $profil = $request->get('profil');
            $success = $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_SIGNALEMENT_LIEN_SUIVI_TO_USAGER,
                    to: 'locataire' === $profil || 'bailleur_occupant' === $profil
                        ? $signalement->getMailOccupant()
                        : $signalement->getMailDeclarant(),
                    signalement: $signalement,
                )
            );

            if ($request->get('preferedResponse') && 'redirection' === $request->get('preferedResponse')) {
                if ($success) {
                    $this->addFlash('success', 'Le lien de suivi a été envoyé par e-mail.');
                } else {
                    $this->addFlash('error', 'Le lien de suivi n\'a pas pu être envoyé par e-mail.');
                }

                return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]));
            }

            if ($success) {
                return $this->json(['success' => true]);
            }

            return $this->json([
                'success' => false,
                'label' => 'Erreur',
                'message' => 'L\'envoi du mail n\'a pas fonctionné, veuillez réessayer ou faire un nouveau signalement.',
            ]);
        }

        return $this->json(['response' => 'error'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/signalement-draft/archive', name: 'archive_draft', methods: 'POST')]
    public function archiveDraft(
        Request $request,
        SignalementDraftRequestSerializer $serializer,
        SignalementDraftManager $signalementDraftManager,
    ): JsonResponse {
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->deserialize(
            $payload = $request->getContent(),
            SignalementDraftRequest::class,
            'json'
        );

        $signalementDraft = $signalementDraftManager->findSignalementDraftByAddressAndMail(
            $signalementDraftRequest,
        );

        if (
            $signalementDraft
        ) {
            $signalementDraft->setStatus(SignalementDraftStatus::ARCHIVE);
            $signalementDraftManager->save($signalementDraft);

            return $this->json(['success' => true]);
        }

        return $this->json(['response' => 'error'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/checkterritory', name: 'front_signalement_check_territory', methods: ['GET'])]
    public function checkTerritory(
        Request $request,
        PostalCodeHomeChecker $postalCodeHomeChecker,
        CommuneRepository $communeRepository,
    ): JsonResponse {
        $postalCode = $request->get('cp');
        $inseeCode = $request->get('insee');
        if (empty($postalCode)) {
            return $this->json([
                'success' => false,
                'message' => 'Le paramètre "cp" est manquant',
                'label' => 'Erreur',
            ]);
        }

        $inseeCode = $postalCodeHomeChecker->normalizeInseeCode($postalCode, $inseeCode);

        if (!empty($inseeCode)) {
            $commune = $communeRepository->findOneBy(['codePostal' => $postalCode, 'codeInsee' => $inseeCode]);
            if (!$commune) {
                \Sentry\captureMessage(sprintf(
                    'Incohérence code postal et code INSEE : Code postal "%s", Code INSEE "%s"',
                    $postalCode,
                    $inseeCode
                ));

                return $this->json([
                    'success' => false,
                    'message' => 'Le paramètre code postal et le code insee ne sont pas cohérent',
                    'label' => 'Erreur', ]);
            }
            if ($postalCodeHomeChecker->isActiveByInseeCode($inseeCode)) {
                return $this->json(['success' => true, 'territoryCode' => $commune->getTerritory()->getZip()]);
            }
        } else {
            if ($postalCodeHomeChecker->isActiveByPostalCode($postalCode)) {
                $commune = $communeRepository->findOneBy(['codePostal' => $postalCode]);

                return $this->json(['success' => true, 'territoryCode' => $commune->getTerritory()->getZip()]);
            }
        }

        $platformName = $request->get('platform_name');
        $messageClosed = '<p>
        Nous ne pouvons malheureusement pas traiter votre demande.<br>
        Le service'.$platformName.'n\'est pas encore ouvert dans votre commune...
        Nous faisons tout pour le rendre disponible dès que possible !
        <br>
        En attendant, nous vous invitons à contacter gratuitement le service "Info logement indigne" au numéro suivant :
        </p>
        <p class="fr-text--center">
            <a href="tel:+33806706806" class="fr-link">
                <span class="fr-icon-phone-line"></span>
                0806 706 806
            </a>
        </p>';

        return $this->json(['success' => false, 'message' => $messageClosed, 'label' => 'Avertissement']);
    }

    #[Route('/signalement/handle', name: 'handle_upload', methods: 'POST')]
    public function handleUpload(
        UploadHandlerService $uploadHandlerService,
        Request $request,
        LoggerInterface $logger,
        ImageManipulationHandler $imageManipulationHandler,
        FileScanner $fileScanner,
    ): JsonResponse {
        if (null !== ($files = $request->files->get('signalement'))) {
            try {
                foreach ($files as $key => $file) {
                    $fileType = 'photos' === $key ? 'photo' : $key;
                    /** @var UploadedFile $file */
                    // PDF files will be checked asynchronously and flagged as suspicious if necessary
                    if (!$fileScanner->isClean($file->getPathname()) && 'application/pdf' !== $file->getMimeType()) {
                        return $this->json(['error' => 'Le fichier est infecté par un virus.'], 400);
                    }
                    $res = $uploadHandlerService->toTempFolder($file, $fileType);
                    if (isset($res['error'])) {
                        throw new \Exception($res['error']);
                    }
                    if (\in_array($file->getMimeType(), File::RESIZABLE_MIME_TYPES)) {
                        $imageManipulationHandler->resize($res['filePath'])->thumbnail();
                    }

                    return $this->json($res);
                }
            } catch (\Exception $exception) {
                $logger->error($exception->getMessage());

                return $this->json(['error' => $exception->getMessage()], 400);
            }
        }
        $logger->error('Un problème lors du téléversement est survenu');

        return $this->json(['error' => 'Aucun fichier n\'a été téléversé'], 400);
    }

    #[Route('/suivre-ma-procedure/{code}', name: 'front_suivi_procedure', methods: ['GET', 'POST'])]
    public function suiviProcedure(
        string $code,
        SignalementRepository $signalementRepository,
        Request $request,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_VIEW, $signalement);
        if (SignalementStatus::ARCHIVED === $signalement->getStatut()) {
            $this->addFlash('error', 'Le lien utilisé est expiré ou invalide.');

            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }
        $suiviAuto = $request->get('suiviAuto');
        // TODO : route à supprimer quelques semaines/mois après la suppression du feature flipping featureSuiviAction (aout 2025)
        // pour ne pas avoir des liens cassés dans les anciens mails
        if (Suivi::ARRET_PROCEDURE == $suiviAuto) {
            return $this->redirectToRoute(
                'front_suivi_signalement_procedure',
                [
                    'code' => $signalement->getCodeSuivi(),
                ]
            );
        }

        return $this->redirectToRoute(
            'front_suivi_signalement_procedure_poursuite',
            [
                'code' => $signalement->getCodeSuivi(),
            ]
        );
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/suivre-mon-signalement/{code}', name: 'front_suivi_signalement', methods: ['GET', 'POST'])]
    public function suiviSignalement(
        string $code,
        SignalementRepository $signalementRepository,
        SuiviRepository $suiviRepository,
        SuiviCategoryMapper $suiviCategoryMapper,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_VIEW, $signalement);

        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();

        if ($redirect = $this->redirectIfTiersNeedsToAcceptCgu($signalement, $signalementUser->getEmail())) {
            return $redirect;
        }

        $demandeLienSignalement = new DemandeLienSignalement();
        $formDemandeLienSignalement = $this->createForm(DemandeLienSignalementType::class, $demandeLienSignalement, [
            'action' => $this->generateUrl('front_demande_lien_signalement'),
        ]);

        $lastSuiviPublic = $suiviRepository->findLastPublicSuivi($signalement);
        $suiviCategory = null;
        if (!$lastSuiviPublic && SignalementStatus::CLOSED === $signalement->getStatut()) {
            $lastSuiviPublic = (new Suivi())->setSignalement($signalement)->setCategory(SuiviCategory::SIGNALEMENT_IS_CLOSED);
        }
        if ($lastSuiviPublic) {
            $suiviCategory = $suiviCategoryMapper->mapFromSuivi($lastSuiviPublic);
        }

        return $this->render('front/suivi_signalement_dashboard.html.twig', [
            'signalement' => $signalement,
            'formDemandeLienSignalement' => $formDemandeLienSignalement,
            'suiviCategory' => $suiviCategory,
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}/accepter-cgu-tiers', name: 'suivi_signalement_tiers_cgu_accept', methods: ['GET', 'POST'])]
    public function suiviSignalementTiersCguAccept(
        string $code,
        SignalementRepository $signalementRepository,
        Request $request,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_VIEW, $signalement);

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('suivi_signalement_tiers_cgu_accept'.$signalement->getCodeSuivi(), $token)) {
                $this->addFlash('error', 'Le jeton CSRF est invalide. Veuillez actualiser la page et réessayer.');
            } elseif (!$request->request->get('accept')) {
                $this->addFlash('error', 'Vous devez accepter les CGU pour accéder au dossier.');
            } else {
                $signalement->setIsCguTiersAccepted(true);
                $signalementRepository->save($signalement, true);

                return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
            }
        }

        return $this->render('front/suivi_signalement_tiers_cgu.html.twig', [
            'signalement' => $signalement,
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}/dossier', name: 'front_suivi_signalement_dossier', methods: ['GET', 'POST'])]
    public function suiviSignalementDossier(
        string $code,
        SignalementRepository $signalementRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_VIEW, $signalement);

        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();

        if ($redirect = $this->redirectIfTiersNeedsToAcceptCgu($signalement, $signalementUser->getEmail())) {
            return $redirect;
        }

        $infoDesordres = $signalementDesordresProcessor->process($signalement);

        $demandeLienSignalement = new DemandeLienSignalement();
        $formDemandeLienSignalement = $this->createForm(DemandeLienSignalementType::class, $demandeLienSignalement, [
            'action' => $this->generateUrl('front_demande_lien_signalement'),
        ]);

        return $this->render('front/suivi_signalement_dossier.html.twig', [
            'signalement' => $signalement,
            'infoDesordres' => $infoDesordres,
            'formDemandeLienSignalement' => $formDemandeLienSignalement,
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}/messages', name: 'front_suivi_signalement_messages', methods: ['GET', 'POST'])]
    public function suiviSignalementMessages(
        string $code,
        SignalementRepository $signalementRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        Request $request,
        FileRepository $fileRepository,
        UploadHandlerService $uploadHandlerService,
        SuiviManager $suiviManager,
        SuiviSeenMarker $suiviSeenMarker,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_VIEW, $signalement);

        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();

        if ($redirect = $this->redirectIfTiersNeedsToAcceptCgu($signalement, $signalementUser->getEmail())) {
            return $redirect;
        }

        $infoDesordres = $signalementDesordresProcessor->process($signalement);

        $formMessage = $this->createForm(MessageUsagerType::class);
        $formMessage->handleRequest($request);
        if ($this->isGranted(SignalementFoVoter::SIGN_USAGER_ADD_SUIVI, $signalement) && $formMessage->isSubmitted() && $formMessage->isValid()) {
            $description = HtmlCleaner::cleanFrontEndEntry($formMessage->get('description')->getData());

            $docs = $fileRepository->findTempForSignalementAndUserIndexedById($signalement, $signalementUser->getUser());
            $filesToAttach = [];
            if (\count($docs)) {
                foreach ($docs as $doc) {
                    if ($uploadHandlerService->deleteIfExpiredFile($doc)) {
                        continue;
                    }
                    $doc->setIsTemp(false);
                    $filesToAttach[] = $doc;
                }
            }

            $suiviType = SignalementStatus::CLOSED === $signalement->getStatut() ? Suivi::TYPE_USAGER_POST_CLOTURE : Suivi::TYPE_USAGER;
            $suiviCategory = SignalementStatus::CLOSED === $signalement->getStatut() ?
                SuiviCategory::MESSAGE_USAGER_POST_CLOTURE :
                SuiviCategory::MESSAGE_USAGER;
            $suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                type: $suiviType,
                category: $suiviCategory,
                user: $signalementUser->getUser(),
                isPublic: true,
                files: $filesToAttach
            );

            $messageRetour = SignalementStatus::CLOSED === $signalement->getStatut() ?
            'Nos services vont prendre connaissance de votre message. Votre dossier est clôturé, vous ne pouvez désormais plus envoyer de message.' :
            'Votre message a bien été envoyé, vous recevrez un e-mail lorsque votre dossier sera mis à jour. N\'hésitez pas à consulter votre page de suivi !';
            $this->addFlash('success', $messageRetour);

            return $this->redirectToRoute('front_suivi_signalement_messages', ['code' => $signalement->getCodeSuivi()]);
        }

        $demandeLienSignalement = new DemandeLienSignalement();
        $formDemandeLienSignalement = $this->createForm(DemandeLienSignalementType::class, $demandeLienSignalement, [
            'action' => $this->generateUrl('front_demande_lien_signalement'),
        ]);

        $suiviSeenMarker->markSeenByUsager($signalement);

        $this->eventDispatcher->dispatch(
            new SuiviViewedEvent($signalement, $signalementUser),
            SuiviViewedEvent::NAME
        );

        return $this->render('front/suivi_signalement_messages.html.twig', [
            'signalement' => $signalement,
            'formMessage' => $formMessage,
            'formDemandeLienSignalement' => $formDemandeLienSignalement,
            'infoDesordres' => $infoDesordres,
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}/completer', name: 'front_suivi_signalement_complete', methods: ['GET', 'POST'])]
    public function suiviSignalementComplete(
        string $code,
        SignalementRepository $signalementRepository,
        Request $request,
        SuiviManager $suiviManager,
        SignalementManager $signalementManager,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_COMPLETE, $signalement);

        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();

        if ($redirect = $this->redirectIfTiersNeedsToAcceptCgu($signalement, $signalementUser->getEmail())) {
            return $redirect;
        }

        $formCoordonneesBailleur = $this->createForm(
            CoordonneesBailleurType::class,
            $signalement,
            ['extended' => true]
        );
        $formCoordonneesBailleur->handleRequest($request);
        if (
            $formCoordonneesBailleur->isSubmitted()
            && $formCoordonneesBailleur->isValid()
        ) {
            /** @var User $user */
            $user = $signalementUser->getUser();
            $signalementManager->save($signalement);
            $usager = ($user === $signalement->getSignalementUsager()?->getOccupant()) ?
                ' ('.UserManager::OCCUPANT.')' :
                ' ('.UserManager::DECLARANT.')';
            $description = $user->getNomComplet(true).$usager.' a mis à jour les coordonnées du bailleur.';
            $description .= '<br>Voici les nouvelles coordonnées :';
            $description .= '<ul>';
            $description .= $signalement->getNomProprio() ? '<li>Nom : '.$signalement->getNomProprio().'</li>' : '';
            $description .= $signalement->getPrenomProprio() ? '<li>Prénom : '.$signalement->getPrenomProprio().'</li>' : '';
            $description .= $signalement->getMailProprio() ? '<li>E-mail : '.$signalement->getMailProprio().'</li>' : '';
            $description .= $signalement->getTelProprio() ? '<li>Téléphone : '.$signalement->getTelProprio().'</li>' : '';
            $description .= $signalement->getTelProprioSecondaire() ? '<li>Téléphone secondaire : '.$signalement->getTelProprioSecondaire().'</li>' : '';
            $description .= $signalement->getAdresseProprio() ? '<li>Adresse : '.$signalement->getAdresseProprio().'</li>' : '';
            $description .= $signalement->getCodePostalProprio() ? '<li>Code postal : '.$signalement->getCodePostalProprio().'</li>' : '';
            $description .= $signalement->getVilleProprio() ? '<li>Ville : '.$signalement->getVilleProprio().'</li>' : '';
            $description .= '</ul>';

            $suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                type: Suivi::TYPE_USAGER,
                category: SuiviCategory::SIGNALEMENT_EDITED_FO,
                user: $user,
                isPublic: true,
            );

            $messageRetour = 'Votre dossier a bien été complété, vous recevrez un e-mail lorsque votre dossier sera mis à jour. N\'hésitez pas à consulter votre page de suivi !';
            $this->addFlash('success', $messageRetour);

            return $this->redirectToRoute('front_suivi_signalement_dossier', ['code' => $signalement->getCodeSuivi()]);
        }

        return $this->render('front/suivi_signalement_complete.html.twig', [
            'signalement' => $signalement,
            'formCoordonneesBailleur' => $formCoordonneesBailleur,
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}/documents', name: 'front_suivi_signalement_documents', methods: ['GET', 'POST'])]
    public function suiviSignalementDocuments(
        string $code,
        SignalementRepository $signalementRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        FileRepository $fileRepository,
        SuiviManager $suiviManager,
        Request $request,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_VIEW, $signalement);
        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();

        if ($redirect = $this->redirectIfTiersNeedsToAcceptCgu($signalement, $signalementUser->getEmail())) {
            return $redirect;
        }

        $infoDesordres = $signalementDesordresProcessor->process($signalement);

        $form = null;
        if ($this->isGranted(SignalementFoVoter::SIGN_USAGER_EDIT, $signalement)) {
            $form = $this->createFormBuilder(null, ['allow_extra_fields' => true])->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $docs = $fileRepository->findTempForSignalementAndUserIndexedById($signalement, $signalementUser->getUser());
                    $filesToAttach = [];
                    if (isset($form->getExtraData()['file'])) {
                        foreach ($form->getExtraData()['file'] as $fileId) {
                            if (isset($docs[$fileId])) {
                                $docs[$fileId]->setIsTemp(false);
                                $filesToAttach[] = $docs[$fileId];
                            }
                        }
                    }
                    if ($filesToAttach) {
                        $descriptionDetails = 'un document.';
                        if (\count($filesToAttach) > 1) {
                            $descriptionDetails = 'des documents.';
                        }
                        $suiviManager->createSuivi(
                            signalement: $signalement,
                            description: UserManager::OCCUPANT === $signalementUser->getType() ? 'L\'occupant a ajouté '.$descriptionDetails : 'Le déclarant a ajouté '.$descriptionDetails,
                            type: Suivi::TYPE_USAGER,
                            category: SuiviCategory::MESSAGE_USAGER,
                            user: $signalementUser->getUser(),
                            isPublic: true,
                            files: $filesToAttach
                        );
                        $this->addFlash('success', 'Vos documents ont bien été enregistrés.');
                    }

                    return $this->redirectToRoute('front_suivi_signalement_documents', ['code' => $signalement->getCodeSuivi()]);
                }
                $this->addFlash('error', 'Une erreur est survenue lors de l\'enregistrement des documents.');
            }
        }

        return $this->render('front/suivi_signalement_documents.html.twig', [
            'signalement' => $signalement,
            'infoDesordres' => $infoDesordres,
            'form' => $form,
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}/procedure', name: 'front_suivi_signalement_procedure', methods: ['GET', 'POST'])]
    public function suiviSignalementProcedure(
        string $code,
        SignalementRepository $signalementRepository,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_VIEW, $signalement);
        if (!$this->isGranted(SignalementFoVoter::SIGN_USAGER_EDIT, $signalement)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour effectuer cette action.');

            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }
        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();

        if ($redirect = $this->redirectIfTiersNeedsToAcceptCgu($signalement, $signalementUser->getEmail())) {
            return $redirect;
        }

        return $this->render('front/suivi_signalement_cancel_procedure_intro.html.twig', [
            'signalement' => $signalement,
            'usager' => $signalementUser->getUser(),
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}/procedure/abandon', name: 'front_suivi_signalement_procedure_abandon', methods: ['GET', 'POST'])]
    public function suiviSignalementProcedureAbandon(
        Request $request,
        string $code,
        SignalementRepository $signalementRepository,
        SignalementManager $signalementManager,
        SuiviManager $suiviManager,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_VIEW, $signalement);
        if ($signalement->getIsUsagerAbandonProcedure()) {
            $this->addFlash('error', 'L\'administration a déjà été informée de votre volonté d\'arrêter la procédure.');

            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }
        if (!$this->isGranted(SignalementFoVoter::SIGN_USAGER_EDIT, $signalement)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour effectuer cette action.');

            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }
        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();

        if ($redirect = $this->redirectIfTiersNeedsToAcceptCgu($signalement, $signalementUser->getEmail())) {
            return $redirect;
        }

        $user = $signalementUser->getUser();

        $form = $this->createForm(UsagerCancelProcedureType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $signalement->setIsUsagerAbandonProcedure(true);

            if (SignalementStatus::INJONCTION_BAILLEUR === $signalement->getStatut()) {
                $signalement->setStatut(SignalementStatus::INJONCTION_CLOSED);
                $category = SuiviCategory::INJONCTION_BAILLEUR_CLOTURE_PAR_USAGER;
                $description = $user->getNomComplet().' a clôturé son dossier en démarche accélérée pour le motif suivant :
                    '.$form->get('reason')->getData().\PHP_EOL
                    .'Détails du motif d\'arrêt de procédure : '.$form->get('details')->getData();
            } else {
                $category = SuiviCategory::DEMANDE_ABANDON_PROCEDURE;
                $description = $user->getNomComplet().' souhaite fermer son dossier sur '
                    .$this->getParameter('platform_name')
                    .' pour le motif suivant : '.$form->get('reason')->getData().\PHP_EOL
                    .'Détails du motif d\'arrêt de procédure : '.$form->get('details')->getData();
            }

            $suiviManager->createSuivi(
                signalement: $signalement,
                description: HtmlCleaner::cleanFrontEndEntry($description),
                type: Suivi::TYPE_USAGER,
                category: $category,
                user: $user,
                isPublic: true,
            );

            $signalementManager->save($signalement);
            $this->addFlash('success', 'Votre demande d\'arrêt de procédure a bien été prise en compte. Elle sera examinée par l\'administration.');

            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }

        return $this->render('front/suivi_signalement_cancel_procedure_validation.html.twig', [
            'signalement' => $signalement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}/procedure/poursuite', name: 'front_suivi_signalement_procedure_poursuite', methods: ['GET', 'POST'])]
    public function suiviSignalementProcedurePoursuite(
        Request $request,
        string $code,
        SignalementRepository $signalementRepository,
        SignalementManager $signalementManager,
        SuiviManager $suiviManager,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_VIEW, $signalement);
        if (false === $signalement->getIsUsagerAbandonProcedure()) {
            $this->addFlash('error', 'L\'administration a déjà été informée de votre volonté de poursuivre la procédure.');

            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }
        if (!$this->isGranted(SignalementFoVoter::SIGN_USAGER_EDIT, $signalement)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour effectuer cette action.');

            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }

        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();

        if ($redirect = $this->redirectIfTiersNeedsToAcceptCgu($signalement, $signalementUser->getEmail())) {
            return $redirect;
        }

        $user = $signalementUser->getUser();

        $form = $this->createForm(UsagerPoursuivreProcedureType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $signalement->setIsUsagerAbandonProcedure(false);

            $description = $user->getNomComplet().' a indiqué vouloir poursuivre la procédure sur '
                .$this->getParameter('platform_name').\PHP_EOL
                .'Commentaire : '.$form->get('details')->getData();

            $suiviManager->createSuivi(
                signalement: $signalement,
                description: HtmlCleaner::cleanFrontEndEntry($description),
                type: Suivi::TYPE_USAGER,
                category: SuiviCategory::DEMANDE_POURSUITE_PROCEDURE,
                user: $user,
                isPublic: true,
            );

            $signalementManager->save($signalement);
            $this->addFlash('success', 'Votre demande de poursuivre la procédure a bien été prise en compte. Elle a été transmise à l\'administration.');

            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }

        return $this->render('front/suivi_signalement_poursuivre_procedure_validation.html.twig', [
            'signalement' => $signalement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}/procedure/bascule', name: 'front_suivi_signalement_procedure_bascule', methods: ['GET', 'POST'])]
    public function suiviSignalementProcedureBascule(
        Request $request,
        string $code,
        SignalementRepository $signalementRepository,
        SignalementManager $signalementManager,
        SuiviManager $suiviManager,
        AutoAssigner $autoAssigner,
        InjonctionBailleurService $injonctionBailleurService,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_BASCULE_PROCEDURE, $signalement);

        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();

        if ($redirect = $this->redirectIfTiersNeedsToAcceptCgu($signalement, $signalementUser->getEmail())) {
            return $redirect;
        }

        $user = $signalementUser->getUser();

        $form = $this->createForm(UsagerBasculeProcedureType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $description = $user->getNomComplet().' a indiqué vouloir basculer de la démarche accélérée vers la procédure administrative';
            $description .= \PHP_EOL.'Commentaire : '.$form->get('details')->getData();
            $entityManager->beginTransaction();
            try {
                $suiviManager->createSuivi(
                    signalement: $signalement,
                    description: HtmlCleaner::cleanFrontEndEntry($description),
                    type: Suivi::TYPE_USAGER,
                    category: SuiviCategory::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_USAGER,
                    user: $user,
                    isPublic: true,
                );

                $injonctionBailleurService->switchFromInjonctionToProcedure($signalement);
                $signalementManager->save($signalement);
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                $logger->critical($e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue veuillez réessayer.');

                return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
            }
            $autoAssigner->assignOrSendNewSignalementNotification($signalement);

            $this->addFlash('success', 'Votre demande a bien été prise en compte. Votre signalement a été transmis à l\'administration.');

            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }

        return $this->render('front/suivi_signalement_poursuivre_procedure_bascule.html.twig', [
            'signalement' => $signalement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}/bailleur-prevenu', name: 'front_suivi_signalement_bailleur_prevenu', methods: ['GET', 'POST'])]
    public function signalementBailleurPrevenu(
        string $code,
        SignalementRepository $signalementRepository,
        SignalementManager $signalementManager,
        SuiviManager $suiviManager,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted('SIGN_USAGER_EDIT', $signalement);

        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();

        if ($redirect = $this->redirectIfTiersNeedsToAcceptCgu($signalement, $signalementUser->getEmail())) {
            return $redirect;
        }

        if ($signalement->getIsProprioAverti()
            || ProfileDeclarant::BAILLEUR === $signalement->getProfileDeclarant()
            || ProfileDeclarant::BAILLEUR_OCCUPANT === $signalement->getProfileDeclarant()
        ) {
            $this->addFlash('error', 'Le bailleur est déjà prévenu.');

            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }

        $user = $signalementUser->getUser();

        $description = $user->getNomComplet(true).' a indiqué que le bailleur a été prévenu.';

        $suiviManager->createSuivi(
            signalement: $signalement,
            description: $description,
            type: Suivi::TYPE_USAGER,
            category: SuiviCategory::MESSAGE_USAGER,
            user: $user,
            isPublic: true,
        );

        $informationProcedure = new InformationProcedure();
        if (!empty($signalement->getInformationProcedure())) {
            $informationProcedure = clone $signalement->getInformationProcedure();
        }
        $informationProcedure->setInfoProcedureBailleurPrevenu('oui');
        $signalement->setInformationProcedure($informationProcedure);
        $signalement->setIsProprioAverti(true);
        $signalementManager->save($signalement);

        $this->addFlash('success', 'Votre modification a bien été prise en compte.');

        return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
    }

    #[Route('/suivre-mon-signalement/{code}/coordonnees-tiers', name: 'front_suivi_signalement_coordonnees_tiers', methods: ['GET', 'POST'])]
    public function signalementCoordonneesTiers(
        string $code,
        Request $request,
        SignalementRepository $signalementRepository,
        SignalementManager $signalementManager,
        SuiviManager $suiviManager,
        UserManager $userManager,
        NotificationMailerRegistry $notificationMailerRegistry,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted('SIGN_USAGER_EDIT', $signalement);

        // On bloque si tiers déjà renseigné ou si créé par tiers
        if (!empty($signalement->getMailDeclarant()) || ($signalement->isV2() && $signalement->getIsNotOccupant())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(
            UsagerCoordonneesTiersType::class,
            $signalement,
            ['extended' => true],
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Create user corresponding to declarant
            $userManager->createUsagerFromSignalement($signalement, UserManager::DECLARANT);

            $signalement->setIsCguTiersAccepted(false);
            $signalementManager->save($signalement);

            $suiviManager->addInviteSuiviFromFo($signalement);

            $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_INVITE_TIERS,
                    to: $signalement->getMailDeclarant(),
                    signalement: $signalement,
                )
            );

            $this->addFlash('success', 'L\'invitation a été transmise à la personne qui pourra suivre votre dossier.');

            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }

        return $this->render('front/suivi_signalement_coordonnees_tiers.html.twig', [
            'signalement' => $signalement,
            'form' => $form->createView(),
        ]);
    }

    private function redirectIfTiersNeedsToAcceptCgu(Signalement $signalement, ?string $userEmail): ?Response
    {
        if ($userEmail !== $signalement->getMailDeclarant()) {
            return null;
        }

        // If null, not invited yet ; if true, already accepted
        if (false !== $signalement->getIsCguTiersAccepted()) {
            return null;
        }

        return $this->redirectToRoute('suivi_signalement_tiers_cgu_accept', ['code' => $signalement->getCodeSuivi()]);
    }
}
