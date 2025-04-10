<?php

namespace App\Controller\Back;

use App\Entity\File;
use App\Entity\User;
use App\Form\UserNotificationEmailType;
use App\Manager\UserManager;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
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
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo/profil')]
class ProfilController extends AbstractController
{
    private const ERROR_MSG = 'Une erreur s\'est produite. Veuillez actualiser la page.';

    #[Route('/', name: 'back_profil', methods: ['GET', 'POST'])]
    public function index(
        UserRepository $userRepository,
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
        $notificationEmailForm = $this->createForm(UserNotificationEmailType::class, $user, ['action' => $this->generateUrl('back_profil_edit_notification_email')]);
        return $this->render('back/profil/index.html.twig', [
            'activeTerritoryAdminsByTerritory' => $activeTerritoryAdminsByTerritory,
            'notificationEmailForm' => $notificationEmailForm,
        ]);
    }

    #[Route('/edit-infos', name: 'back_profil_edit_infos', methods: ['POST'])]
    public function editInfos(
        Request $request,
        ManagerRegistry $doctrine,
        ValidatorInterface $validator,
        UploadHandlerService $uploadHandlerService,
        LoggerInterface $logger,
        ImageManipulationHandler $imageManipulationHandler,
        FileScanner $fileScanner,
    ): Response {
        $payload = $request->getPayload()->all();
        /** @var User $user */
        $user = $this->getUser();
        if ($this->isCsrfTokenValid(
            'profil_edit_infos',
            $payload['_token']
        )) {
            $avatarFile = $request->files->get('profil_edit_infos')['avatar'] ?? null;
            $errorMessage = [];
            if (empty($payload['profil_edit_infos']['prenom'])) {
                $errorMessage['errors']['profil_edit_infos[prenom]']['errors'][] = 'Le prénom ne peut pas être vide';
            } elseif (mb_strlen($payload['profil_edit_infos']['prenom']) > 255) {
                $errorMessage['errors']['profil_edit_infos[prenom]']['errors'][] = 'Le prénom ne doit pas dépasser 255 caractères';
            }
            if (empty($payload['profil_edit_infos']['nom'])) {
                $errorMessage['errors']['profil_edit_infos[nom]']['errors'][] = 'Le nom ne peut pas être vide';
            } elseif (mb_strlen($payload['profil_edit_infos']['nom']) > 255) {
                $errorMessage['errors']['profil_edit_infos[nom]']['errors'][] = 'Le nom ne doit pas dépasser 255 caractères';
            }
            if (!empty($payload['profil_edit_infos']['fonction']) && mb_strlen($payload['profil_edit_infos']['fonction']) > 50) {
                $errorMessage['errors']['profil_edit_infos[fonction]']['errors'][] = 'La fonction ne doit pas dépasser 50 caractères';
            }
            // Validation du fichier avatar
            if (empty($errorMessage) && $avatarFile instanceof UploadedFile) {
                $errors = $validator->validate(
                    $avatarFile,
                    [
                        new Assert\Image([
                            'maxSize' => '5M',
                            'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                            'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG ou GIF)',
                            'maxSizeMessage' => 'La taille du fichier ne doit pas dépasser 5 Mo.',
                        ]),
                    ]
                );
                if (count($errors) > 0) {
                    foreach ($errors as $error) {
                        $errorMessage['errors']['profil_edit_infos[avatar]']['errors'][] = $error->getMessage();
                    }
                } elseif (!$fileScanner->isClean($avatarFile->getPathname())) {
                    $errorMessage['errors']['profil_edit_infos[avatar]']['errors'][] = 'Le fichier est infecté';
                } else {
                    try {
                        $res = $uploadHandlerService->toTempFolder($avatarFile, 'avatar');

                        if (\is_array($res) && isset($res['error'])) {
                            throw new \Exception($res['error']);
                        }
                        $res = $uploadHandlerService->setKey('avatar');

                        if (\in_array($avatarFile->getMimeType(), File::RESIZABLE_MIME_TYPES)) {
                            $imageManipulationHandler->avatar($res['filePath']);
                            $uploadHandlerService->moveFilePath($res['filePath']);
                        }

                        if ($user->getAvatarFilename()) {
                            $uploadHandlerService->deleteSingleFile($user->getAvatarFilename());
                        }

                        $user->setAvatarFilename($res['file']);
                    } catch (FileException $e) {
                        $logger->error($e->getMessage());
                        $errorMessage['errors']['profil_edit_infos[avatar]']['errors'][] = 'Échec du téléchargement de l\'avatar.';
                    }
                }
            }

            if (empty($errorMessage)) {
                $response = ['code' => Response::HTTP_OK];
                $user->setPrenom($payload['profil_edit_infos']['prenom']);
                $user->setNom($payload['profil_edit_infos']['nom']);
                $user->setFonction($payload['profil_edit_infos']['fonction']);
                $doctrine->getManager()->persist($user);
                $doctrine->getManager()->flush();
                $this->addFlash('success', 'Les informations de votre profil ont bien été modifiées.');
            } else {
                $response = ['code' => Response::HTTP_BAD_REQUEST];
                $response = [...$response, ...$errorMessage];
            }
        } else {
            $response = [
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => self::ERROR_MSG,
            ];
        }

        return $this->json($response, $response['code']);
    }

    #[Route('/delete-avatar', name: 'back_profil_delete_avatar', methods: ['GET', 'POST'])]
    public function deleteAvatar(
        ManagerRegistry $doctrine,
        UploadHandlerService $uploadHandlerService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->getAvatarFilename()) {
            $uploadHandlerService->deleteSingleFile($user->getAvatarFilename());
            $user->setAvatarFilename(null);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'L\'avatar a bien été supprimé.');

            return $this->redirectToRoute('back_profil', [], Response::HTTP_SEE_OTHER);
        }

        $this->addFlash('error', 'Une erreur est survenue lors de la suppression...');

        return $this->redirectToRoute('back_profil', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/edit-email', name: 'back_profil_edit_email', methods: ['POST'])]
    public function editEmail(
        Request $request,
        ManagerRegistry $doctrine,
        NotificationMailerRegistry $notificationMailerRegistry,
        UserRepository $userRepository,
        PartnerRepository $partnerRepository,
    ): Response {
        $payload = $request->getPayload()->all();
        /** @var User $user */
        $user = $this->getUser();
        if ($this->isCsrfTokenValid(
            'profil_edit_email',
            $payload['_token']
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

                    $this->addFlash('success', 'Votre adresse e-mail a bien été confirmée !');
                    $response = ['code' => Response::HTTP_OK];
                } else {
                    $response = ['code' => Response::HTTP_BAD_REQUEST];
                    $response = [...$response, ...$errorMessage];
                }
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
            $response = [
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => self::ERROR_MSG,
            ];
        }

        return $this->json($response, $response['code']);
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
        $payload = $request->getPayload()->all();

        if ($request->isMethod('POST')
            && $this->isCsrfTokenValid('profil_edit_password', $payload['_token'])
        ) {
            $errorMessages = [];
            $passwordCurrent = $payload['password-current'];
            $password = $payload['password'];
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

            $this->addFlash('success', 'Votre mot de passe a bien été modifié.');
            $response = ['code' => Response::HTTP_OK];
        } else {
            $response = [
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => self::ERROR_MSG,
            ];
        }

        return $this->json($response, $response['code']);
    }
}
