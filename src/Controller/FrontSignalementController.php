<?php

namespace App\Controller;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\DocumentType;
use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\SignalementDraftFactory;
use App\Factory\SuiviFactory;
use App\Manager\SignalementDraftManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\CommuneRepository;
use App\Repository\SignalementDraftRepository;
use App\Repository\SignalementRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\ImageManipulationHandler;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\PostalCodeHomeChecker;
use App\Service\Signalement\SignalementFileProcessor;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/')]
class FrontSignalementController extends AbstractController
{
    #[Route('/signalement', name: 'front_signalement')]
    public function index(
    ): Response {
        return $this->render('front/nouveau_formulaire.html.twig', [
            'uuid_signalement' => null,
        ]);
    }

    #[Route('/signalement-draft/{uuid}', name: 'front_nouveau_formulaire_edit', methods: 'GET')]
    public function edit(
        SignalementDraft $signalementDraft
    ): Response {
        return $this->render('front/nouveau_formulaire.html.twig', [
            'uuid_signalement' => $signalementDraft->getUuid(),
        ]);
    }

    #[Route('/signalement-draft/envoi', name: 'envoi_nouveau_signalement_draft', methods: 'POST')]
    public function sendSignalementDraft(
        Request $request,
        SignalementDraftRequestSerializer $serializer,
        SignalementDraftManager $signalementDraftManager,
        ValidatorInterface $validator,
    ): Response {
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

    #[Route('/signalement-draft/check', name: 'check_signalement_draft_existe', methods: 'POST')]
    public function checkSignalementDraftExists(
        Request $request,
        SignalementDraftRequestSerializer $serializer,
        SignalementDraftManager $signalementDraftManager,
        ValidatorInterface $validator,
        SignalementDraftFactory $signalementDraftFactory,
        SignalementDraftRepository $signalementDraftRepository,
    ): Response {
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
            $dataToHash = $signalementDraftFactory->getEmailDeclarant($signalementDraftRequest);
            $dataToHash .= $signalementDraftRequest->getAdresseLogementAdresse();
            $hash = hash('sha256', $dataToHash);

            $existingSignalementDraft = $signalementDraftRepository->findOneBy(
                [
                    'checksum' => $hash,
                    'status' => SignalementDraftStatus::EN_COURS,
                ],
                [
                    'id' => 'DESC',
                ]
            );

            if (null !== $existingSignalementDraft) {
                return $this->json([
                    'already_exists' => true,
                    'uuid' => $existingSignalementDraft->getUuid(),
                    'created_at' => $existingSignalementDraft->getCreatedAt(),
                    'updated_at' => $existingSignalementDraft->getUpdatedAt(),
                ]);
            }

            return $this->json([
                'already_exists' => false,
                'uuid' => $signalementDraftManager->create(
                    $signalementDraftRequest,
                    json_decode($payload, true)
                ),
            ]);
        }

        return $this->json($errors);
    }

    #[Route('/signalement-draft/{uuid}/envoi', name: 'mise_a_jour_nouveau_signalement_draft', methods: 'PUT')]
    public function updateSignalementDraft(
        Request $request,
        SignalementDraftRequestSerializer $serializer,
        SignalementDraftManager $signalementDraftManager,
        ValidatorInterface $validator,
        SignalementDraft $signalementDraft,
    ): Response {
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

    #[Route('/signalement-draft/{uuid}/informations', name: 'informations_signalement_draft', methods: 'GET')]
    public function getSignalementDraft(
        SignalementDraft $signalementDraft,
    ): Response {
        return $this->json([
            'signalement' => SignalementDraftStatus::EN_COURS === $signalementDraft->getStatus()
                ? $signalementDraft :
                null,
        ]);
    }

    #[Route('/signalement-draft/{uuid}/send_mail', name: 'send_mail_continue_from_draft')]
    public function sendMailContinueFromDraft(
        NotificationMailerRegistry $notificationMailerRegistry,
        SignalementDraft $signalementDraft,
        Request $request
    ): Response {
        if (
            $request->isMethod('POST')
            && $signalementDraft
            && SignalementDraftStatus::EN_COURS === $signalementDraft->getStatus()
        ) {
            $success = $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CONTINUE_FROM_DRAFT,
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
            ]);
        }

        return $this->json(['response' => 'error'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/signalement-draft/{uuid}/archive', name: 'archive_draft')]
    public function archiveDraft(
        SignalementDraft $signalementDraft,
        Request $request,
        SignalementDraftManager $signalementDraftManager
    ): Response {
        if (
            $request->isMethod('POST')
            && $signalementDraft
            && SignalementDraftStatus::EN_COURS === $signalementDraft->getStatus()
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
        CommuneRepository $communeRepository
    ): Response {
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
        }
        if ($postalCodeHomeChecker->isActive($postalCode, $inseeCode)) {
            return $this->json(['success' => true]);
        }

        $messageClosed = '<p>
        Nous ne pouvons malheureusement pas traiter votre demande.<br>
        Le service Histologe n\'est pas encore ouvert dans votre commune...
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
        ImageManipulationHandler $imageManipulationHandler
    ) {
        if (null !== ($files = $request->files->get('signalement'))) {
            try {
                foreach ($files as $key => $file) {
                    $res = $uploadHandlerService->toTempFolder($file);
                    if (\is_array($res) && isset($res['error'])) {
                        throw new \Exception($res['error']);
                    }
                    $res = $uploadHandlerService->setKey($key);
                    if (\in_array($file->getMimeType(), ImageManipulationHandler::IMAGE_MIME_TYPES)) {
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

    #[Route('/suivre-ma-procedure/{code}', name: 'front_suivi_procedure', methods: 'GET')]
    public function suiviProcedure(
        string $code,
        SignalementRepository $signalementRepository,
        Request $request,
        UserManager $userManager,
        SuiviManager $suiviManager,
        EntityManagerInterface $entityManager,
        SuiviFactory $suiviFactory
    ) {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        if (!$signalement) {
            $this->addFlash('error', 'Le lien utilisé est expiré ou invalide.');

            return $this->redirectToRoute('home');
        }
        $requestEmail = $request->get('from');
        $fromEmail = \is_array($requestEmail) ? array_pop($requestEmail) : $requestEmail;
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
        || \in_array($signalement->getStatut(), [Signalement::STATUS_CLOSED, Signalement::STATUS_REFUSED])) {
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
                'description' => $description,
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
                $entityManager->persist($signalement);
                $this->addFlash('success', "Les services ont été informés de votre volonté d'arrêter la procédure.
                Si vous le souhaitez, vous pouvez préciser la raison de l'arrêt de procédure
                en envoyant un message via le formulaire ci-dessous.");
            } else {
                $description = $user->getNomComplet().' ('.$type.') a indiqué vouloir poursuivre la procédure.';
                $this->addFlash('success', "Les services ont été informés de votre volonté de poursuivre la procédure.
                N'hésitez pas à mettre à jour votre situation en envoyant un message via le formulaire ci-dessous.");
            }

            $params = [
                'type' => SUIVI::TYPE_USAGER,
                'description' => $description,
            ];

            $suivi = $suiviFactory->createInstanceFrom(
                $user,
                $signalement,
                $params,
                true
            );
            $entityManager->persist($suivi);
            $entityManager->flush();

            return $this->redirectToRoute(
                'front_suivi_signalement',
                ['code' => $signalement->getCodeSuivi(), 'from' => $fromEmail]
            );
        }

        return $this->render('front/suivi_signalement.html.twig', [
            'signalement' => $signalement,
            'email' => $fromEmail,
            'type' => $type,
            'suiviAuto' => $suiviAuto,
        ]);
    }

    #[Route('/suivre-mon-signalement/{code}', name: 'front_suivi_signalement', methods: 'GET')]
    public function suiviSignalement(
        string $code,
        SignalementRepository $signalementRepository,
        Request $request,
        UserManager $userManager
    ) {
        if ($signalement = $signalementRepository->findOneByCodeForPublic($code)) {
            $requestEmail = $request->get('from');
            $fromEmail = \is_array($requestEmail) ? array_pop($requestEmail) : $requestEmail;

            $user = $userManager->getOrCreateUserForSignalementAndEmail($signalement, $fromEmail);
            $type = $userManager->getUserTypeForSignalementAndUser($signalement, $user);

            return $this->render('front/suivi_signalement.html.twig', [
                'signalement' => $signalement,
                'email' => $fromEmail,
                'type' => $type,
            ]);
        }
        $this->addFlash('error', 'Le lien utilisé est invalide, vérifiez votre saisie.');

        return $this->redirectToRoute('home');
    }

    #[Route('/suivre-mon-signalement/{code}/response', name: 'front_suivi_signalement_user_response', methods: 'POST')]
    public function postUserResponse(
        string $code,
        SignalementRepository $signalementRepository,
        UserManager $userManager,
        Request $request,
        EntityManagerInterface $entityManager,
        SuiviFactory $suiviFactory,
        SignalementFileProcessor $signalementFileProcessor
    ): RedirectResponse {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        if (!$signalement) {
            $this->addFlash('error', 'Le lien utilisé est expiré ou invalide, vérifiez votre saisie.');

            return $this->redirectToRoute('home');
        }
        if (!$this->isCsrfTokenValid('signalement_front_response_'.$signalement->getUuid(), $request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');

            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }
        if (\in_array($signalement->getStatut(), [Signalement::STATUS_CLOSED, Signalement::STATUS_REFUSED])) {
            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }

        $email = $request->get('signalement_front_response')['email'];
        $user = $userManager->getOrCreateUserForSignalementAndEmail($signalement, $email);
        $suivi = $suiviFactory->createInstanceFrom(
            user: $user,
            signalement: $signalement,
            params: ['type' => Suivi::TYPE_USAGER],
            isPublic: true,
        );

        $description = htmlspecialchars(
            nl2br($request->get('signalement_front_response')['content']),
            \ENT_QUOTES,
            'UTF-8'
        );

        $fileList = $descriptionList = [];
        if ($data = $request->get('signalement')) {
            if (isset($data['files'])) {
                $dataFiles = $data['files'];
                foreach ($dataFiles as $inputName => $files) {
                    list($files, $descriptions) = $signalementFileProcessor->process(
                        $dataFiles,
                        $inputName,
                        DocumentType::AUTRE
                    );
                    $fileList = [...$fileList, ...$files];
                    $descriptionList = [...$descriptionList, ...$descriptions];
                }
                unset($data['files']);
            }
            if (!empty($descriptionList)) {
                $description .= '<br>Ajout de pièces au signalement<ul>'
                    .implode('', $descriptionList).'</ul>';
                $signalementFileProcessor->addFilesToSignalement($fileList, $signalement, $user);
            }
        }

        $suivi->setDescription($description);
        $entityManager->persist($suivi);
        $entityManager->flush();
        $this->addFlash('success', <<<SUCCESS
                Votre message a bien été envoyé, vous recevrez un email lorsque votre dossier sera mis à jour.
                N'hésitez pas à consulter votre page de suivi !
                SUCCESS);

        return $this->redirectToRoute(
            'front_suivi_signalement',
            ['code' => $signalement->getCodeSuivi(), 'from' => $email]
        );
    }
}
