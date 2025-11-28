<?php

namespace App\Controller\Back;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use App\Form\UserNotificationEmailType;
use App\Form\UserProfilInfoType;
use App\Manager\UserManager;
use App\Manager\UserSignalementSubscriptionManager;
use App\Repository\AffectationRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Service\FormHelper;
use App\Service\ImageManipulationHandler;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Security\FileScanner;
use App\Service\UploadHandlerService;
use App\Validator\EmailFormatValidator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo/profil')]
class ProfilController extends AbstractController
{
    private const ERROR_MSG = 'Une erreur s\'est produite. Veuillez actualiser la page.';

    private function getHtmlTargetContentsForProfilMesInformations(User $user): array
    {
        $formProfilInfo = $this->createForm(UserProfilInfoType::class, $user, ['action' => $this->generateUrl('back_profil_edit_infos')]);

        return [
            [
                'target' => '#mes-informations',
                'content' => $this->renderView('back/profil/_mes-informations.html.twig'),
            ],
            [
                'target' => '#fr-modal-profil-edit-infos-content',
                'content' => $this->renderView('back/profil/_modal_profil_infos_content.html.twig', ['formProfilInfo' => $formProfilInfo]),
            ],
            [
                'target' => '#nav-bo-profile-link',
                'content' => $this->renderView('back/_nav_bo_profile_link.html.twig'),
            ],
        ];
    }

    #[Route('/', name: 'back_profil', methods: ['GET', 'POST'])]
    public function index(
        UserRepository $userRepository,
        SignalementRepository $signalementRepository,
        UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository,
    ): Response {
        $activeTerritoryAdminsByTerritory = [];
        /** @var User $user */
        $user = $this->getUser();
        $territories = $user->getPartnersTerritories();
        foreach ($territories as $territory) {
            $territoryId = $territory->getId();
            $territoryAdmins = $userRepository->findActiveTerritoryAdmins($territoryId);
            $activeTerritoryAdminsByTerritory[$territoryId] = $territoryAdmins;
        }
        $formProfilInfo = $this->createForm(UserProfilInfoType::class, $user, ['action' => $this->generateUrl('back_profil_edit_infos')]);
        $notificationEmailForm = $this->createForm(UserNotificationEmailType::class, $user, ['action' => $this->generateUrl('back_profil_edit_notification_email')]);

        $nbActiveSignalements = $signalementRepository->getActiveSignalementsForUser(user: $user, count: true);
        $nbActiveSignalementsWithInteractions = $signalementRepository->getActiveSignalementsWithInteractionsForUser(user: $user, count: true);
        $nbSubsOnActiveSignalements = $userSignalementSubscriptionRepository->countSubscriptionsOnActiveSignalementsForUser($user);
        $nbSubsOnSignalementsWithoutInteractions = $userSignalementSubscriptionRepository->getSubscriptionsOnSignalementsWithoutInteractionsForUser(user: $user, count: true);

        return $this->render('back/profil/index.html.twig', [
            'activeTerritoryAdminsByTerritory' => $activeTerritoryAdminsByTerritory,
            'formProfilInfo' => $formProfilInfo,
            'notificationEmailForm' => $notificationEmailForm,
            'nbActiveSignalements' => $nbActiveSignalements,
            'nbActiveSignalementsWithInteractions' => $nbActiveSignalementsWithInteractions,
            'nbSubsOnActiveSignalements' => $nbSubsOnActiveSignalements,
            'nbSubsOnSignalementsWithoutInteractions' => $nbSubsOnSignalementsWithoutInteractions,
        ]);
    }

    #[Route('/edit-notification-email', name: 'back_profil_edit_notification_email', methods: ['POST'])]
    public function editNotificationEmail(
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(UserNotificationEmailType::class, $user, ['action' => $this->generateUrl('back_profil_edit_notification_email')]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setIsMailingActive(true);
            $entityManager->flush();
            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Vos préférences en matière de notifications par e-mail ont bien été enregistrées.'];
            $htmlTargetContents = [
                [
                    'target' => '#notifications-email',
                    'content' => $this->renderView('back/profil/_notifications-email.html.twig'),
                ],
            ];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
        }
        $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm($form)];

        return $this->json($response, $response['code']);
    }

    #[Route('/edit-infos', name: 'back_profil_edit_infos', methods: ['POST'])]
    public function editInfos(
        Request $request,
        EntityManagerInterface $entityManager,
        UploadHandlerService $uploadHandlerService,
        LoggerInterface $logger,
        ImageManipulationHandler $imageManipulationHandler,
        FileScanner $fileScanner,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(UserProfilInfoType::class, $user, ['action' => $this->generateUrl('back_profil_edit_infos')]);

        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            return $this->json(['code' => Response::HTTP_BAD_REQUEST]);
        }
        if (!$form->isValid()) {
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $form, withPrefix: true)];

            return $this->json($response, $response['code']);
        }

        $errorMessage = [];
        $avatarFile = $form->get('avatar')->getData();
        if ($avatarFile instanceof UploadedFile) {
            if (!$fileScanner->isClean($avatarFile->getPathname())) {
                $errorMessage['errors']['user_profil_info[avatar]']['errors'][] = 'Le fichier est infecté';
            } else {
                try {
                    $res = $uploadHandlerService->toTempFolder($avatarFile);

                    if (isset($res['error'])) {
                        throw new \Exception($res['error']);
                    }

                    $imageManipulationHandler->avatar($res['filePath']);
                    $uploadHandlerService->moveFilePath($res['filePath']);

                    if ($user->getAvatarFilename()) {
                        $uploadHandlerService->deleteSingleFile($user->getAvatarFilename());
                    }

                    $user->setAvatarFilename($res['file']);
                } catch (FileException $e) {
                    $logger->error($e->getMessage());
                    $errorMessage['errors']['user_profil_info[avatar]']['errors'][] = 'Échec du téléchargement de l\'avatar.';
                }
            }
        }

        if (empty($errorMessage)) {
            $entityManager->flush();

            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les informations de votre profil ont bien été modifiées.'];
            $htmlTargetContents = $this->getHtmlTargetContentsForProfilMesInformations($user);

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
        }
        $response = ['code' => Response::HTTP_BAD_REQUEST];
        $response = [...$response, ...$errorMessage];

        return $this->json($response, (int) $response['code']);
    }

    #[Route('/delete-avatar', name: 'back_profil_delete_avatar', methods: ['GET', 'POST'])]
    public function deleteAvatar(
        ManagerRegistry $doctrine,
        UploadHandlerService $uploadHandlerService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->getAvatarFilename()) {
            $uploadHandlerService->deleteSingleFile($user->getAvatarFilename());
            $user->setAvatarFilename(null);
            $doctrine->getManager()->flush();

            $flashMessages[] = ['type' => 'success', 'title' => 'Avatar supprimé', 'message' => 'L\'avatar a bien été supprimé.'];
            $htmlTargetContents = $this->getHtmlTargetContentsForProfilMesInformations($user);

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
        }
        $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur de suppression', 'message' => 'Une erreur est survenue lors de la suppression, veuilez réessayer.'];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
    }

    #[Route('/edit-email', name: 'back_profil_edit_email', methods: ['POST'])]
    public function editEmail(
        Request $request,
        ManagerRegistry $doctrine,
        NotificationMailerRegistry $notificationMailerRegistry,
        UserRepository $userRepository,
        PartnerRepository $partnerRepository,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        /** @var User $user */
        $user = $this->getUser();
        if ($this->isCsrfTokenValid(
            'profil_edit_email',
            (string) $payload['_token']
        )) {
            $errorMessage = [];

            if (isset($payload['profil_edit_email[code]'])) {
                // Étape 2: Validation du code
                $authCode = $payload['profil_edit_email[code]'];
                $userExist = $userRepository->findOneBy(['email' => $user->getTempEmail()]);
                if ($userExist) {
                    $errorMessage['errors']['profil_edit_email[code]']['errors'][] = 'Un utilisateur existe déjà avec cette adresse e-mail.';
                }
                $partnerExist = $partnerRepository->findOneBy(['email' => $user->getTempEmail()]);
                if ($partnerExist) {
                    $errorMessage['errors']['profil_edit_email[code]']['errors'][] = 'Un partenaire existe déjà avec cette adresse e-mail.';
                }
                if ('' === $authCode) {
                    $errorMessage['errors']['profil_edit_email[code]']['errors'][] = 'Le code est obligatoire.';
                } elseif ($authCode !== $user->getEmailAuthCode()) {
                    $errorMessage['errors']['profil_edit_email[code]']['errors'][] = 'Le code est incorrect.';
                }
                if (null === $user->getTempEmail()) {
                    // ne doit pas arriver
                    $errorMessage['errors']['profil_edit_email[code]']['errors'][] = 'Il n\'y a pas d\'adresse e-mail enregistrée à modifier';
                }

                if (empty($errorMessage)) {
                    $user->setEmail($user->getTempEmail());
                    $user->setEmailAuthCode(null);
                    $user->setTempEmail(null);
                    $doctrine->getManager()->persist($user);
                    $doctrine->getManager()->flush();

                    $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Votre adresse e-mail a bien été confirmée.'];
                    $htmlTargetContents = [
                        [
                            'target' => '#adresse-email',
                            'content' => $this->renderView('back/profil/_adresse-email.html.twig'),
                        ],
                    ];

                    return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
                }
                $response = ['code' => Response::HTTP_BAD_REQUEST];
                $response = [...$response, ...$errorMessage];
            } else {
                // Étape 1: Envoi du code de confirmation par email
                $email = $payload['profil_edit_email[email]'];

                $userExist = $userRepository->findOneBy(['email' => $email]);
                if ($userExist) {
                    $errorMessage['errors']['profil_edit_email[email]']['errors'][] = 'Un utilisateur existe déjà avec cette adresse e-mail.';
                }
                $partnerExist = $partnerRepository->findOneBy(['email' => $email]);
                if ($partnerExist) {
                    $errorMessage['errors']['profil_edit_email[email]']['errors'][] = 'Un partenaire existe déjà avec cette adresse e-mail.';
                }
                if ('' === $email) {
                    $errorMessage['errors']['profil_edit_email[email]']['errors'][] = 'Ce champ est obligatoire.';
                } elseif (!EmailFormatValidator::validate($email)) {
                    $errorMessage['errors']['profil_edit_email[email]']['errors'][] = 'Veuillez saisir une adresse e-mail au format adresse@email.fr.';
                } elseif ($email === $user->getEmail()) {
                    $errorMessage['errors']['profil_edit_email[email]']['errors'][] = 'Veuillez saisir une adresse e-mail différente de l\'actuelle';
                }
                if (empty($errorMessage)) {
                    $user->setEmailAuthCode(bin2hex(random_bytes(3)));
                    $user->setTempEmail($email);
                    $doctrine->getManager()->flush();
                    $notificationMailerRegistry->send(
                        new NotificationMail(
                            type: NotificationMailerType::TYPE_PROFIL_EDIT_EMAIL,
                            to: $email,
                            user: $user
                        )
                    );

                    $response = [
                        'code' => Response::HTTP_NO_CONTENT,
                    ];
                } else {
                    $response = ['code' => Response::HTTP_BAD_REQUEST];
                    $response = [...$response, ...$errorMessage];
                }
            }
        } else {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => self::ERROR_MSG];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }

        return $this->json($response, (int) $response['code']);
    }

    #[Route('/edit-password', name: 'back_profil_edit_password', methods: ['POST'])]
    public function editPassword(
        Request $request,
        UserManager $userManager,
        ValidatorInterface $validator,
        NotificationMailerRegistry $notificationMailerRegistry,
        PasswordHasherFactoryInterface $passwordHasherFactory,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();

        if ($request->isMethod('POST')
            && $this->isCsrfTokenValid('profil_edit_password', (string) $payload['_token'])
        ) {
            $errorMessages = [];
            /** @var string $passwordCurrent */
            $passwordCurrent = $payload['password-current'];
            /** @var string $password */
            $password = $payload['password'];
            /** @var string $passwordRepeat */
            $passwordRepeat = $payload['password-repeat'];

            if (!$passwordHasherFactory->getPasswordHasher($user)->verify($user->getPassword(), $passwordCurrent)) {
                $errorMessages['errors']['password-current']['errors'][] = 'Le mot de passe ne correspond pas à celui enregistré.';
            }
            if ('' === $password) {
                $errorMessages['errors']['password']['errors'][] = 'Ce champ est obligatoire.';
            }
            if ($password !== $passwordRepeat) {
                $errorMessages['errors']['password-repeat']['errors'][] = 'Les mots de passes renseignés doivent être identiques.';
            }
            if ($password === $user->getEmail()) {
                $errorMessages['errors']['password']['errors'][] = 'Le mot de passe ne doit pas être votre e-mail.';
            }

            $oldPassword = $user->getPassword();
            $user->setPassword($password);
            $violations = $validator->validate($user, null, ['password']);
            $user->setPassword($oldPassword);
            if (\count($violations)) {
                foreach ($violations as $violation) {
                    $errorMessages['errors'][$violation->getPropertyPath()]['errors'][] = $violation->getMessage();
                }
            }
            if (\count($errorMessages)) {
                $response = ['code' => Response::HTTP_BAD_REQUEST];
                $response = [...$response, ...$errorMessages];

                return $this->json($response, $response['code']);
            }

            $user = $userManager->resetPassword($user, $password);
            $payload['password'] = $payload['password-repeat'] = $payload['password-current'] = null;
            $password = $passwordRepeat = $oldPassword = $passwordCurrent = null;

            $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_PROFIL_EDIT_PASSWORD,
                    to: $user->getEmail(),
                    user: $user
                )
            );
            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Votre mot de passe a bien été modifié.'];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true]);
        }
        $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => self::ERROR_MSG];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
    }

    #[Route('/dismiss-modal-duplicate-addresses', name: 'dismiss_modal_duplicate_addresses', methods: ['POST'])]
    public function dismissModalDuplicateAddresses(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('modal_duplicate_addresses', (string) $request->request->get('_token'))) {
            /** @var User $user */
            $user = $this->getUser();
            $user->setDuplicateModalDismissed();
            $entityManager->flush();
        }

        return $this->json(['status' => 'success']);
    }

    #[Route('/subscriptions-changes', name: 'profil_subscriptions_changes', methods: ['GET'])]
    public function subscriptionsChanges(
        Request $request,
        EntityManagerInterface $entityManager,
        SignalementRepository $signalementRepository,
        UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository,
        AffectationRepository $affectationRepository,
        UserSignalementSubscriptionManager $userSignalementSubscriptionManager,
    ): Response {
        if (!$this->isCsrfTokenValid('subscriptions_changes', (string) $request->query->get('_token'))) {
            $this->addFlash('error', 'Une erreur est survenue lors de la modification de vos abonnements');

            return $this->redirectToRoute('back_profil');
        }
        $sendFlashSuccess = false;
        /** @var User $user */
        $user = $this->getUser();
        if ('unsubscribe' === $request->query->get('action')) {
            /** @var UserSignalementSubscription[] $subsOnInactiveSignalements */
            $subsOnInactiveSignalements = $userSignalementSubscriptionRepository->getSubscriptionsOnSignalementsWithoutInteractionsForUser(user: $user);
            $lastUserOnSignalements = [];
            foreach ($subsOnInactiveSignalements as $sub) {
                if (!$user->isSuperAdmin()) { // les SA peuvent toujours se désabonner
                    $partner = $user->getPartnerInTerritory($sub->getSignalement()->getTerritory());
                    $affectation = $affectationRepository->findOneBy(['signalement' => $sub->getSignalement(), 'partner' => $partner, 'statut' => AffectationStatus::ACCEPTED]);
                    $subsForPartner = $userSignalementSubscriptionRepository->findForSignalementAndPartner($sub->getSignalement(), $partner);
                    if ($affectation && 1 === count($subsForPartner)) {
                        $lastUserOnSignalements[$sub->getSignalement()->getId()] = $sub->getSignalement()->getReference();
                        continue; // ne pas permettre de se désabonner si le partenaire est affecté et que l'utilisateur est le seul abonné pour ce partenaire
                    }
                }
                $entityManager->remove($sub);
                $sendFlashSuccess = true;
            }
            if (count($lastUserOnSignalements) > 0) {
                $msg = 'Vous ne pouvez pas vous désabonner des signalements suivants car vous êtes le dernier utilisateur abonné pour votre partenaire : '.implode(', ', $lastUserOnSignalements);
                $this->addFlash('error', $msg);
            }
        } else {
            /** @var Signalement[] $activeSignalements */
            $activeSignalements = $signalementRepository->getActiveSignalementsForUser($user);
            foreach ($activeSignalements as $signalement) {
                $userSignalementSubscriptionManager->createOrGet(userToSubscribe: $user, signalement: $signalement, createdBy: $user);
                $sendFlashSuccess = true;
            }
        }
        $entityManager->flush();
        if ($sendFlashSuccess) {
            $this->addFlash('success', 'Vos abonnements ont bien été mis à jour.');
        }

        return $this->redirectToRoute('back_profil');
    }
}
