<?php

namespace App\Controller;

use App\Dto\DemandeLienSignalement;
use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Entity\Suivi;
use App\Entity\User;
use App\Event\SuiviViewedEvent;
use App\Form\DemandeLienSignalementType;
use App\Form\MessageUsagerType;
use App\Form\UsagerCancelProcedureType;
use App\Manager\SignalementDraftManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\CommuneRepository;
use App\Repository\FileRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Security\User\SignalementUser;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\ImageManipulationHandler;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Security\FileScanner;
use App\Service\Signalement\PostalCodeHomeChecker;
use App\Service\Signalement\SignalementDesordresProcessor;
use App\Service\Signalement\SignalementDuplicateChecker;
use App\Service\Signalement\SuiviSeenMarker;
use App\Service\SuiviCategorizerService;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/')]
class SignalementController extends AbstractController
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        #[Autowire(env: 'FEATURE_SECURE_UUID_URL')]
        private readonly bool $featureSecureUuidUrl,
        #[Autowire(env: 'FEATURE_SUIVI_ACTION')]
        private readonly bool $featureSuiviAction,
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
            $errors = ['Merci de sélectionner le type de déclarant'];
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
        if (empty($postalCode)) {
            return $this->json([
                'success' => false,
                'message' => 'Le paramètre "cp" est manquant',
                'label' => 'Erreur',
            ]);
        }

        $inseeCode = $request->get('insee');
        if (!empty($inseeCode)) {
            $commune = $communeRepository->findOneBy(['codePostal' => $postalCode, 'codeInsee' => $inseeCode]);
            if (!$commune) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le paramètre code postal et le code insee ne sont pas cohérent',
                    'label' => 'Erreur', ]);
            }
            if ($postalCodeHomeChecker->isActiveByInseeCode($inseeCode)) {
                return $this->json(['success' => true]);
            }
        } else {
            if ($postalCodeHomeChecker->isActiveByPostalCode($postalCode)) {
                return $this->json(['success' => true]);
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
                    /** @var UploadedFile $file */
                    // PDF files will be checked asynchronously and flagged as suspicious if necessary
                    if (!$fileScanner->isClean($file->getPathname()) && 'application/pdf' !== $file->getMimeType()) {
                        return $this->json(['error' => 'Le fichier est infecté par un virus.'], 400);
                    }
                    $res = $uploadHandlerService->toTempFolder($file, $key);
                    if (\is_array($res) && isset($res['error'])) {
                        throw new \Exception($res['error']);
                    }
                    $res = $uploadHandlerService->setKey($key);
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
        UserManager $userManager,
        SuiviManager $suiviManager,
        EntityManagerInterface $entityManager,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        Security $security,
        AuthenticationUtils $authenticationUtils,
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        HtmlSanitizerInterface $htmlSanitizer,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);

        if (!$signalement) {
            $this->addFlash('error', 'Le lien utilisé est expiré ou invalide.');

            return $this->render('front/flash-messages.html.twig');
        }

        $requestEmail = $request->get('from');
        $fromEmail = \is_array($requestEmail) ? array_pop($requestEmail) : $requestEmail;

        /** @var SignalementUser $currentUser */
        $currentUser = $security->getUser();
        if ((!$security->isGranted('ROLE_SUIVI_SIGNALEMENT') || $currentUser->getCodeSuivi() !== $code)
            && $this->featureSecureUuidUrl
        ) {
            // get the login error if there is one
            $error = $authenticationUtils->getLastAuthenticationError();

            return $this->render('security/login_suivi_signalement.html.twig', [
                'signalement' => $signalement,
                'error' => $error,
            ]);
        }

        $suiviAuto = $request->get('suiviAuto');

        /** @var User $userOccupant */
        $userOccupant = $userManager->createUsagerFromSignalement($signalement, UserManager::OCCUPANT);
        /** @var User $userDeclarant */
        $userDeclarant = $userManager->createUsagerFromSignalement($signalement, UserManager::DECLARANT);
        $type = null;
        $user = null;
        if ($userOccupant && $fromEmail === $userOccupant->getEmail()) {
            $type = UserManager::OCCUPANT;
            $user = $userOccupant;
        } elseif ($userDeclarant && $fromEmail === $userDeclarant->getEmail()) {
            $type = UserManager::DECLARANT;
            $user = $userDeclarant;
        }

        if (!$user
        || !\in_array($suiviAuto, [Suivi::POURSUIVRE_PROCEDURE, Suivi::ARRET_PROCEDURE])
        || \in_array($signalement->getStatut(), [SignalementStatus::CLOSED, SignalementStatus::REFUSED])) {
            $this->addFlash('error', 'Le lien utilisé est invalide.');

            return $this->redirectToRoute(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi(), 'from' => $fromEmail]
            );
        }

        if ($signalement->getIsUsagerAbandonProcedure()) {
            $this->addFlash('error', 'Les services ont déjà été informés de votre volonté d\'arrêter la procédure.
                    Si vous le souhaitez, vous pouvez préciser la raison de l\'arrêt de procédure
                    en envoyant un message via le formulaire ci-dessous.');

            return $this->redirectToRoute(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi(), 'from' => $fromEmail]
            );
        }

        if (Suivi::POURSUIVRE_PROCEDURE === $suiviAuto) {
            $description = $user->getNomComplet().' ('.$type.') a indiqué vouloir poursuivre la procédure.';
            $suiviPoursuivreProcedure = $suiviManager->findOneBy([
                'description' => $htmlSanitizer->sanitize($description),
                'signalement' => $signalement,
            ]);
            if (null !== $suiviPoursuivreProcedure) {
                $this->addFlash('error', 'Les services ont déjà été informés de votre volonté de continuer la procédure.
                        Si vous le souhaitez, vous pouvez envoyer un message via le formulaire ci-dessous.');

                return $this->redirectToRoute(
                    'front_suivi_signalement',
                    ['code' => $signalement->getCodeSuivi(), 'from' => $fromEmail]
                );
            }
        }

        $token = $request->get('_token');
        $tokenValid = $this->isCsrfTokenValid('suivi_procedure', $token);
        if ($token && !$tokenValid) {
            $this->addFlash('error', 'Token CSRF invalide, merci de réessayer.');
        }
        if ($token && $tokenValid) {
            if (Suivi::ARRET_PROCEDURE === $suiviAuto) {
                $description = $user->getNomComplet().' ('.$type.') a demandé l\'arrêt de la procédure.';
                $signalement->setIsUsagerAbandonProcedure(true);
                $categorySuivi = SuiviCategory::DEMANDE_ABANDON_PROCEDURE;
                $entityManager->persist($signalement);
                $this->addFlash('success', "Les services ont été informés de votre volonté d'arrêter la procédure.
                Si vous le souhaitez, vous pouvez préciser la raison de l'arrêt de procédure
                en envoyant un message via le formulaire ci-dessous.");
            } else {
                $categorySuivi = SuiviCategory::DEMANDE_POURSUITE_PROCEDURE;
                $description = $user->getNomComplet().' ('.$type.') a indiqué vouloir poursuivre la procédure.';
                $this->addFlash('success', "Les services ont été informés de votre volonté de poursuivre la procédure.
                N'hésitez pas à mettre à jour votre situation en envoyant un message via le formulaire ci-dessous.");
            }

            $suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                type: Suivi::TYPE_USAGER,
                category: $categorySuivi,
                isPublic: true,
                user: $user,
            );

            return $this->redirectToRoute(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi(), 'from' => $fromEmail]
            );
        }

        $infoDesordres = $signalementDesordresProcessor->process($signalement);

        return $this->render('front/suivi_signalement.html.twig', [
            'signalement' => $signalement,
            'email' => $fromEmail,
            'type' => $type,
            'suiviAuto' => $suiviAuto,
            'infoDesordres' => $infoDesordres,
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}', name: 'front_suivi_signalement', methods: ['GET', 'POST'])]
    public function suiviSignalement(
        string $code,
        SignalementRepository $signalementRepository,
        SuiviRepository $suiviRepository,
        Request $request,
        UserManager $userManager,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        Security $security,
        AuthenticationUtils $authenticationUtils,
        SuiviCategorizerService $suiviCategorizerService,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code, false);
        if (!$signalement) {
            $this->addFlash('error', 'Le lien utilisé est invalide, vérifiez votre saisie.');

            return $this->render('front/flash-messages.html.twig');
        }

        // TODO : delete when remove FEATURE_SECURE_UUID_URL
        $requestEmail = $request->get('from');
        $fromEmail = \is_array($requestEmail) ? array_pop($requestEmail) : $requestEmail;
        // TODO : get type from auth when remove FEATURE_SECURE_UUID_URL
        $user = $userManager->getOrCreateUserForSignalementAndEmail($signalement, $fromEmail);
        $type = $userManager->getUserTypeForSignalementAndUser($signalement, $user);

        if ($this->featureSecureUuidUrl) { // TODO Remove FEATURE_SECURE_UUID_URL
            /** @var SignalementUser $currentUser */
            $currentUser = $security->getUser();
            if (!$security->isGranted('ROLE_SUIVI_SIGNALEMENT') || $currentUser->getCodeSuivi() !== $code) {
                // get the login error if there is one
                $error = $authenticationUtils->getLastAuthenticationError();

                return $this->render('security/login_suivi_signalement.html.twig', [
                    'signalement' => $signalement,
                    'error' => $error,
                ]);
            }
            $user = $currentUser->getUser();
        }

        $demandeLienSignalement = new DemandeLienSignalement();
        $formDemandeLienSignalement = $this->createForm(DemandeLienSignalementType::class, $demandeLienSignalement, [
            'action' => $this->generateUrl('front_demande_lien_signalement'),
        ]);

        if ($this->featureSuiviAction) {
            $lastSuiviPublic = $suiviRepository->findLastPublicSuivi($signalement, $user);
            $suiviCategory = null;
            if (!$lastSuiviPublic && SignalementStatus::CLOSED === $signalement->getStatut()) {
                $suiviCategory = $suiviCategorizerService->getSuiviCategoryFromEnum(SuiviCategory::SIGNALEMENT_IS_CLOSED);
            } elseif ($lastSuiviPublic) {
                $suiviCategory = $suiviCategorizerService->getSuiviCategoryFromSuivi($lastSuiviPublic);
            }

            return $this->render('front/suivi_signalement_dashboard.html.twig', [
                'signalement' => $signalement,
                'formDemandeLienSignalement' => $formDemandeLienSignalement,
                'suiviCategory' => $suiviCategory,
            ]);
        }

        $infoDesordres = $signalementDesordresProcessor->process($signalement);

        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();
        $this->eventDispatcher->dispatch(
            new SuiviViewedEvent($signalement, $signalementUser),
            SuiviViewedEvent::NAME
        );

        return $this->render('front/suivi_signalement.html.twig', [
            'signalement' => $signalement,
            'email' => $fromEmail,
            'type' => $type,
            'infoDesordres' => $infoDesordres,
            'formDemandeLienSignalement' => $formDemandeLienSignalement,
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}/dossier', name: 'front_suivi_signalement_dossier', methods: ['GET', 'POST'])]
    public function suiviSignalementDossier(
        string $code,
        SignalementRepository $signalementRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        Security $security,
        AuthenticationUtils $authenticationUtils,
    ): Response {
        if (!$this->featureSuiviAction) {
            throw $this->createNotFoundException();
        }
        $signalement = $signalementRepository->findOneByCodeForPublic($code, false);
        if (!$signalement) {
            $this->addFlash('error', 'Le lien utilisé est invalide, vérifiez votre saisie.');

            return $this->render('front/flash-messages.html.twig');
        }

        /** @var SignalementUser $currentUser */
        $currentUser = $security->getUser();
        if (!$security->isGranted('ROLE_SUIVI_SIGNALEMENT') || $currentUser->getCodeSuivi() !== $code) {
            // get the login error if there is one
            $error = $authenticationUtils->getLastAuthenticationError();

            return $this->render('security/login_suivi_signalement.html.twig', [
                'signalement' => $signalement,
                'error' => $error,
            ]);
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
        Security $security,
        AuthenticationUtils $authenticationUtils,
        Request $request,
        FileRepository $fileRepository,
        UploadHandlerService $uploadHandlerService,
        SuiviManager $suiviManager,
        SuiviSeenMarker $suiviSeenMarker,
    ): Response {
        if (!$this->featureSuiviAction) {
            throw $this->createNotFoundException();
        }
        $signalement = $signalementRepository->findOneByCodeForPublic($code, false);
        if (!$signalement) {
            $this->addFlash('error', 'Le lien utilisé est invalide, vérifiez votre saisie.');

            return $this->render('front/flash-messages.html.twig');
        }
        /** @var SignalementUser $currentUser */
        $currentUser = $security->getUser();
        if (!$security->isGranted('ROLE_SUIVI_SIGNALEMENT') || $currentUser->getCodeSuivi() !== $code) {
            // get the login error if there is one
            $error = $authenticationUtils->getLastAuthenticationError();

            return $this->render('security/login_suivi_signalement.html.twig', [
                'signalement' => $signalement,
                'error' => $error,
            ]);
        }

        $infoDesordres = $signalementDesordresProcessor->process($signalement);

        $formMessage = $this->createForm(MessageUsagerType::class);
        $formMessage->handleRequest($request);
        if ($this->isGranted('SIGN_USAGER_EDIT', $signalement) && $formMessage->isSubmitted() && $formMessage->isValid()) {
            $description = nl2br(htmlspecialchars($formMessage->get('description')->getData(), \ENT_QUOTES, 'UTF-8'));

            $docs = $fileRepository->findBy(['signalement' => $signalement, 'isTemp' => true, 'uploadedBy' => $currentUser->getUser()]);
            if (\count($docs)) {
                $descriptionList = [];
                foreach ($docs as $doc) {
                    if ($uploadHandlerService->deleteIfExpiredFile($doc)) {
                        continue;
                    }
                    $doc->setIsTemp(false);
                    $url = $this->generateUrl('show_file', ['uuid' => $doc->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);
                    $descriptionList[] = '<li><a class="fr-link" target="_blank" rel="noopener" href="'.$url.'">'.$doc->getTitle().'</a></li>';
                }
                $description .= '<br>Ajout de pièces au signalement<ul>'.implode('', $descriptionList).'</ul>';
            }

            $typeSuivi = SignalementStatus::CLOSED === $signalement->getStatut() ? Suivi::TYPE_USAGER_POST_CLOTURE : Suivi::TYPE_USAGER;
            $suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                type: $typeSuivi,
                isPublic: true,
                user: $currentUser->getUser(),
                category: SuiviCategory::MESSAGE_USAGER,
            );

            $messageRetour = SignalementStatus::CLOSED === $signalement->getStatut() ?
            'Nos services vont prendre connaissance de votre message. Votre dossier est clôturé, vous ne pouvez désormais plus envoyer de message.' :
            'Votre message a bien été envoyé, vous recevrez un email lorsque votre dossier sera mis à jour. N\'hésitez pas à consulter votre page de suivi !';
            $this->addFlash('success', $messageRetour);

            return $this->redirectToRoute('front_suivi_signalement_messages', ['code' => $signalement->getCodeSuivi()]);
        }

        $demandeLienSignalement = new DemandeLienSignalement();
        $formDemandeLienSignalement = $this->createForm(DemandeLienSignalementType::class, $demandeLienSignalement, [
            'action' => $this->generateUrl('front_demande_lien_signalement'),
        ]);

        $suiviSeenMarker->markSeenByUsager($signalement);

        $this->eventDispatcher->dispatch(
            new SuiviViewedEvent($signalement, $currentUser),
            SuiviViewedEvent::NAME
        );

        return $this->render('front/suivi_signalement_messages.html.twig', [
            'signalement' => $signalement,
            'formMessage' => $formMessage,
            'formDemandeLienSignalement' => $formDemandeLienSignalement,
            'infoDesordres' => $infoDesordres,
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}/documents', name: 'front_suivi_signalement_documents', methods: ['GET', 'POST'])]
    public function suiviSignalementDocuments(
        string $code,
        SignalementRepository $signalementRepository,
    ): Response {
        if (!$this->featureSuiviAction) {
            throw $this->createNotFoundException();
        }
        $signalement = $signalementRepository->findOneByCodeForPublic($code, false);
        if (!$signalement) {
            $this->addFlash('error', 'Le lien utilisé est invalide, vérifiez votre saisie.');

            return $this->render('front/flash-messages.html.twig');
        }

        return new Response('<html><body>TODO</body></html>');
    }

    #[Route('/suivre-mon-signalement/{code}/procedure', name: 'front_suivi_signalement_procedure', methods: ['GET', 'POST'])]
    public function suiviSignalementProcedure(
        string $code,
        SignalementRepository $signalementRepository,
    ): Response {
        if (!$this->featureSuiviAction) {
            throw $this->createNotFoundException();
        }
        $signalement = $signalementRepository->findOneByCodeForPublic($code, false);
        if (!$signalement) {
            $this->addFlash('error', 'Le lien utilisé est invalide, vérifiez votre saisie.');

            return $this->render('front/flash-messages.html.twig');
        }

        return $this->render('front/suivi_signalement_cancel_procedure_intro.html.twig', [
            'signalement' => $signalement,
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}/procedure/validation', name: 'front_suivi_signalement_procedure_validation', methods: ['GET', 'POST'])]
    public function suiviSignalementProcedureValidation(
        Request $request,
        string $code,
        SignalementRepository $signalementRepository,
        SignalementManager $signalementManager,
        SuiviManager $suiviManager,
        UserManager $userManager,
        Security $security,
        AuthenticationUtils $authenticationUtils,
    ): Response {
        if (!$this->featureSuiviAction) {
            throw $this->createNotFoundException();
        }

        // TODO : empecher si procédure déjà annulée
        $signalement = $signalementRepository->findOneByCodeForPublic($code, false);
        if (!$signalement) {
            $this->addFlash('error', 'Le lien utilisé est invalide, vérifiez votre saisie.');
            return $this->render('front/flash-messages.html.twig');
        }

        $form = $this->createForm(UsagerCancelProcedureType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // TODO : delete when remove FEATURE_SECURE_UUID_URL
            $requestEmail = $request->get('from');
            $fromEmail = \is_array($requestEmail) ? array_pop($requestEmail) : $requestEmail;
            // TODO : get type from auth when remove FEATURE_SECURE_UUID_URL
            $user = $userManager->getOrCreateUserForSignalementAndEmail($signalement, $fromEmail);
            $type = $userManager->getUserTypeForSignalementAndUser($signalement, $user);

            if ($this->featureSecureUuidUrl) { // TODO Remove FEATURE_SECURE_UUID_URL
                /** @var SignalementUser $currentUser */
                $currentUser = $security->getUser();
                if (!$security->isGranted('ROLE_SUIVI_SIGNALEMENT') || $currentUser->getCodeSuivi() !== $code) {
                    // get the login error if there is one
                    $error = $authenticationUtils->getLastAuthenticationError();

                    return $this->render('security/login_suivi_signalement.html.twig', [
                        'signalement' => $signalement,
                        'error' => $error,
                    ]);
                }
                $user = $currentUser->getUser();
            }

            $signalement->setIsUsagerAbandonProcedure(true);

            // $description = $user->getNomComplet().' ('.$type.') a demandé l\'arrêt de la procédure. <br>'
            //     . 'Raison : ' . $form->get('reason')->getData() . '<br>'
            //     . 'Commentaire : ' . $form->get('details')->getData();
            $description = $user->getNomComplet().' souhaite fermer son dossier sur '
                . $this->getParameter('platform_name') // TODO
                . ' pour le motif suivant : ' . $form->get('reason')->getData() . '<br>'
                . 'Détails du motif d\'arrêt de procédure : ' . $form->get('details')->getData();

            $suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                type: Suivi::TYPE_USAGER,
                isPublic: true,
                category: SuiviCategory::DEMANDE_ABANDON_PROCEDURE,
                user: $user,
            );



            // Une notif est envoyée au RT
            // Un mail est envoyé au RT / l'info est incluse dans les mails récap
            // Un mail de confirmation est envoyée au demandeur (voir plus loin)
            // Si demande faite sur un signalement avec tiers : on envoie un mail à l'autre personne (voir plus loin)
            $signalementManager->save($signalement);
            $this->addFlash('success', 'Votre demande d\'arrêt de procédure a bien été prise en compte. Elle sera examinée par l\'administration.');
            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }

        return $this->render('front/suivi_signalement_cancel_procedure_validation.html.twig', [
            'signalement' => $signalement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}/response', name: 'front_suivi_signalement_user_response', methods: 'POST')]
    public function postUserResponse(
        string $code,
        SignalementRepository $signalementRepository,
        UserManager $userManager,
        Request $request,
        EntityManagerInterface $entityManager,
        SuiviManager $suiviManager,
        UploadHandlerService $uploadHandlerService,
        ValidatorInterface $validator,
    ): Response {
        if ($this->featureSuiviAction) {
            throw $this->createNotFoundException();
        }
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        if (!$this->isGranted('SIGN_USAGER_EDIT', $signalement)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour effectuer cette action.');

            return $this->render('front/flash-messages.html.twig');
        }
        if (!$this->isCsrfTokenValid('signalement_front_response_'.$signalement->getUuid(), $request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');

            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }
        $email = $request->get('signalement_front_response')['email'];
        $user = $userManager->getOrCreateUserForSignalementAndEmail($signalement, $email);

        $errors = $validator->validate($request->get('signalement_front_response')['content'], [
            new \Symfony\Component\Validator\Constraints\NotBlank(),
            new \Symfony\Component\Validator\Constraints\Length(['min' => 10]),
        ]);
        foreach ($errors as $error) {
            $this->addFlash('error', $error->getMessage());
        }
        if (\count($errors)) {
            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi(), 'from' => $email]);
        }

        $description = nl2br(htmlspecialchars(
            $request->get('signalement_front_response')['content'],
            \ENT_QUOTES,
            'UTF-8'
        ));

        $docs = $entityManager->getRepository(File::class)->findBy(['signalement' => $signalement, 'isTemp' => true, 'uploadedBy' => $user]);
        if (\count($docs)) {
            $descriptionList = [];
            foreach ($docs as $doc) {
                if ($uploadHandlerService->deleteIfExpiredFile($doc)) {
                    continue;
                }
                $doc->setIsTemp(false);
                $url = $this->generateUrl('show_file', ['uuid' => $doc->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);
                $descriptionList[] = '<li><a class="fr-link" target="_blank" rel="noopener" href="'.$url.'">'.$doc->getTitle().'</a></li>';
            }
            $description .= '<br>Ajout de pièces au signalement<ul>'.implode('', $descriptionList).'</ul>';
        }

        $typeSuivi = SignalementStatus::CLOSED === $signalement->getStatut() ? Suivi::TYPE_USAGER_POST_CLOTURE : Suivi::TYPE_USAGER;
        $suiviManager->createSuivi(
            signalement: $signalement,
            description: $description,
            type: $typeSuivi,
            category: SuiviCategory::MESSAGE_USAGER,
            isPublic: true,
            user: $user,
        );

        $messageRetour = SignalementStatus::CLOSED === $signalement->getStatut() ?
        'Nos services vont prendre connaissance de votre message. Votre dossier est clôturé, vous ne pouvez désormais plus envoyer de message.' :
        'Votre message a bien été envoyé, vous recevrez un email lorsque votre dossier sera mis à jour.
                N\'hésitez pas à consulter votre page de suivi !';
        $this->addFlash('success', $messageRetour);

        return $this->redirectToRoute(
            'front_suivi_signalement',
            ['code' => $signalement->getCodeSuivi(), 'from' => $email]
        );
    }
}
