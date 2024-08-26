<?php

namespace App\Controller\Back;

use App\Entity\File;
use App\Entity\User;
use App\Manager\UserManager;
use App\Service\ImageManipulationHandler;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Security\FileScanner;
use App\Service\UploadHandlerService;
use App\Validator\EmailFormatValidator;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo/profil')]
class ProfilController extends AbstractController
{
    private const ERROR_MSG = 'Une erreur s\'est produite. Veuillez actualiser la page.';

    #[Route('/', name: 'back_profil', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER_PARTNER')]
    public function index(
    ): Response {
        return $this->render('back/profil/index.html.twig');
    }

    #[Route('/edit-infos', name: 'back_profil_edit_infos', methods: ['POST'])]
    #[IsGranted('ROLE_USER_PARTNER')]
    public function editInfos(
        Request $request,
        ManagerRegistry $doctrine,
        ValidatorInterface $validator,
        UploadHandlerService $uploadHandlerService,
        LoggerInterface $logger,
        ImageManipulationHandler $imageManipulationHandler,
        FileScanner $fileScanner
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
            }
            if (empty($payload['profil_edit_infos']['nom'])) {
                $errorMessage['errors']['profil_edit_infos[nom]']['errors'][] = 'Le nom ne peut pas être vide';
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
                        // TODO : vérifier que c'est un fichier différent
                        $res = $uploadHandlerService->toTempFolder($avatarFile, 'avatar');

                        if (\is_array($res) && isset($res['error'])) {
                            throw new \Exception($res['error']);
                        }
                        $res = $uploadHandlerService->setKey('avatar');

                        if (\in_array($avatarFile->getMimeType(), File::IMAGE_MIME_TYPES)) {
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
    #[IsGranted('ROLE_USER_PARTNER')]
    public function deleteAvatar(
        Request $request,
        ManagerRegistry $doctrine,
        UploadHandlerService $uploadHandlerService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        // && $this->isCsrfTokenValid('tag_delete', $request->request->get('_token'))
        if ($user->getAvatarFilename()) {
            $uploadHandlerService->deleteSingleFile($user->getAvatarFilename());
            $user->setAvatarFilename(null);
            $doctrine->getManager()->persist($user);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'L\'avatar a bien été supprimé.');

            return $this->redirectToRoute('back_profil', [], Response::HTTP_SEE_OTHER);
        }

        $this->addFlash('error', 'Une erreur est survenue lors de la suppression...');

        return $this->redirectToRoute('back_profil', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/edit-email', name: 'back_profil_edit_email', methods: ['POST'])]
    #[IsGranted('ROLE_USER_PARTNER')]
    public function editEmail(
        Request $request,
        ManagerRegistry $doctrine,
        NotificationMailerRegistry $notificationMailerRegistry,
    ): Response {
        $payload = $request->getPayload()->all();
        /** @var User $user */
        $user = $this->getUser();
        if ($this->isCsrfTokenValid(
            'profil_edit_email',
            $payload['_token']
        )) {
            $errorMessage = [];
            $email = $payload['profil_edit_email[email]'];
            if (isset($payload['profil_edit_email[code]'])) {
                // Étape 2: Validation du code
                $authCode = $payload['profil_edit_email[code]'];
                if ('' === $authCode) {
                    $errorMessage['errors']['profil_edit_email[code]']['errors'][] = 'Le code est obligatoire.';
                } elseif ($authCode !== $user->getEmailAuthCode()) {
                    $errorMessage['errors']['profil_edit_email[code]']['errors'][] = 'Le code est incorrect.';
                }

                if (empty($errorMessage)) {
                    $user->setEmail($email);
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
                if ('' === $email) {
                    $errorMessage['errors']['profil_edit_email[email]']['errors'][] = 'Ce champ est obligatoire.';
                } elseif (!EmailFormatValidator::validate($email)) {
                    $errorMessage['errors']['profil_edit_email[email]']['errors'][] = 'Veuillez saisir une adresse email au format adresse@email.fr.';
                }

                if (empty($errorMessage)) {
                    $user->setEmailAuthCode(bin2hex(random_bytes(3)));
                    $doctrine->getManager()->persist($user);
                    $doctrine->getManager()->flush();
                    $notificationMailerRegistry->send(
                        new NotificationMail(
                            type: NotificationMailerType::TYPE_PROFIL_EDIT_EMAIL,
                            to: $email,
                            territory: $user->getTerritory(),
                            user: $user
                        )
                    );

                    $response = [
                        'code' => Response::HTTP_NO_CONTENT,
                        'message' => 'on doit ouvrir une autre modale maintenant',
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
    #[IsGranted('ROLE_USER_PARTNER')]
    public function editPassword(
        Request $request,
        UserManager $userManager,
        ValidatorInterface $validator,
        NotificationMailerRegistry $notificationMailerRegistry,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $payload = $request->getPayload()->all();

        if ($request->isMethod('POST')
            && $this->isCsrfTokenValid('profil_edit_password', $payload['_token'])
        ) {
            $password = $payload['password'];
            $passwordRepeat = $payload['password-repeat'];
            if ($password !== $passwordRepeat) {
                $this->addFlash('error', 'Les mots de passes renseignés doivent être identiques.');

                return $this->redirectToRoute('back_profil', [], Response::HTTP_SEE_OTHER);
            }
            $user->setPassword($password);
            $errors = $validator->validate($user, null, ['password']);
            if (\count($errors) > 0) {
                $errorMessage = '<ul>';
                foreach ($errors as $error) {
                    $errorMessage .= '<li>'.$error->getMessage().'</li>';
                }
                $errorMessage .= '</ul>';
                $this->addFlash('error error-raw', $errorMessage);

                return $this->redirectToRoute('back_profil', [], Response::HTTP_SEE_OTHER);
            }

            // Erreurs possibles

            // Champ vide : Ce champ est obligatoire.
            // Mot de passe identique à l'actuel : Vous utilisez déjà ce mot de passe. Veuillez saisir un nouveau mot de passe.
            // Pas de correspondance : Les mots de passes renseignés doivent être identiques.
            // Mauvais format : Le mot de passe doit contenir : 12 caractères, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial.

            $user = $userManager->resetPassword($user, $password);

            $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_PROFIL_EDIT_PASSWORD,
                    to: $user->getEmail(),
                    territory: $user->getTerritory(),
                    user: $user
                )
            );

            $this->addFlash('success', 'Votre mot de passe a bien été modifié.');

            return $this->redirectToRoute('back_profil', [], Response::HTTP_SEE_OTHER);
        }

        $this->addFlash('error', self::ERROR_MSG);

        return $this->redirectToRoute('back_profil', [], Response::HTTP_SEE_OTHER);
    }
}
