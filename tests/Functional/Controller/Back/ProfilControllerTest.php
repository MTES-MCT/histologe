<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ImageManipulationHandler;
use App\Service\Security\FileScanner;
use App\Service\UploadHandlerService;
use App\Tests\SessionHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class ProfilControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private RouterInterface $router;
    private User $user;
    private UploadHandlerService|MockObject $uploadHandlerServiceMock;
    private FileScanner|MockObject $fileScannerMock;
    private ImageManipulationHandler|MockObject $imageManipulationHandlerMock;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->router = self::getContainer()->get(RouterInterface::class);

        $this->uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        static::getContainer()->set('App\Service\UploadHandlerService', $this->uploadHandlerServiceMock);

        $this->fileScannerMock = $this->createMock(FileScanner::class);
        static::getContainer()->set('App\Service\Security\FileScanner', $this->fileScannerMock);

        $this->imageManipulationHandlerMock = $this->createMock(ImageManipulationHandler::class);
        static::getContainer()->set('App\Service\ImageManipulationHandler', $this->imageManipulationHandlerMock);

        $this->user = $this->userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $this->client->loginUser($this->user);
    }

    public function testIndex(): void
    {
        $route = $this->router->generate('back_profil');
        $this->client->request('GET', $route);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mon profil');
    }

    public function testEditInfosSuccess(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_infos');

        $route = $this->router->generate('back_profil_edit_infos');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_infos' => [
                'prenom' => 'John',
                'nom' => 'Doe',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson(json_encode(['code' => Response::HTTP_OK]));
        $this->assertEquals('Doe', $this->user->getNom());
        $this->assertEquals('John', $this->user->getPrenom());
    }

    public function testEditInfosWithAvatarSuccess(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_infos');

        $imageFile = new UploadedFile(
            __DIR__.'/../../../files/sample.jpg',
            'sample.jpg',
            'image/jpeg',
            null,
            true
        );

        $this->fileScannerMock->expects($this->once())
        ->method('isClean')
        ->with($this->equalTo($imageFile->getPathname()))
        ->willReturn(true);
        $this->uploadHandlerServiceMock->expects($this->once())
        ->method('toTempFolder')
        ->willReturn(['file' => 'avatarTitle.jpg', 'filePath' => 'path']);
        $this->uploadHandlerServiceMock->expects($this->once())
        ->method('setKey')
        ->willReturn(['file' => 'avatarTitle.jpg', 'filePath' => 'path']);
        $this->uploadHandlerServiceMock->expects($this->once())
        ->method('moveFilePath');
        $this->imageManipulationHandlerMock->expects($this->once())
        ->method('avatar');

        $route = $this->router->generate('back_profil_edit_infos');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_infos' => [
                'prenom' => 'John',
                'nom' => 'Doe',
            ],
        ], ['profil_edit_infos' => [
            'avatar' => $imageFile,
        ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson(json_encode(['code' => Response::HTTP_OK]));
        $this->assertEquals('Doe', $this->user->getNom());
        $this->assertEquals('John', $this->user->getPrenom());
        $this->assertEquals('avatarTitle.jpg', $this->user->getAvatarFilename());
    }

    public function testEditInfosWithAvatarNotClean(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_infos');

        $imageFile = new UploadedFile(
            __DIR__.'/../../../files/sample.jpg',
            'sample.jpg',
            'image/jpeg',
            null,
            true
        );

        $this->fileScannerMock->expects($this->once())
        ->method('isClean')
        ->with($this->equalTo($imageFile->getPathname()))
        ->willReturn(false);

        $route = $this->router->generate('back_profil_edit_infos');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_infos' => [
                'prenom' => 'John',
                'nom' => 'Doe',
            ],
        ], ['profil_edit_infos' => [
            'avatar' => $imageFile,
        ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $this->assertJson(json_encode([
            'errors' => [
                'profil_edit_infos[avatar]' => [
                    'errors' => ['Le fichier est infecté'],
                ],
            ],
        ]));
    }

    public function testEditInfosWithAvatarBadFormat(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_infos');

        $imageFile = new UploadedFile(
            __DIR__.'/../../../files/sample.pdf',
            'sample.pdf',
            'application/pdf',
            null,
            true
        );

        $route = $this->router->generate('back_profil_edit_infos');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_infos' => [
                'prenom' => 'John',
                'nom' => 'Doe',
            ],
        ], ['profil_edit_infos' => [
            'avatar' => $imageFile,
        ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $this->assertJson(json_encode([
            'errors' => [
                'profil_edit_infos[avatar]' => [
                    'errors' => ['Veuillez télécharger une image valide (JPEG, PNG ou GIF)'],
                ],
            ],
        ]));
    }

    public function testEditInfosInvalidCsrfToken(): void
    {
        $route = $this->router->generate('back_profil_edit_infos');

        $this->client->request('POST', $route, [
            '_token' => 'invalid_csrf_token',
            'profil_edit_infos' => [
                'prenom' => 'John',
                'nom' => 'Doe',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testEditInfosInvalidData(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_infos');

        $route = $this->router->generate('back_profil_edit_infos');

        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_infos' => [
                'prenom' => '',
                'nom' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $this->assertJson(json_encode([
            'errors' => [
                'profil_edit_infos[prenom]' => [
                    'errors' => ['Le prénom ne peut pas être vide'],
                ],
                'profil_edit_infos[nom]' => [
                    'errors' => ['Le nom ne peut pas être vide'],
                ],
            ],
        ]));
    }

    public function testDeleteAvatarSuccess(): void
    {
        $this->user->setAvatarFilename('path/to/avatar.jpg');
        $this->userRepository->save($this->user, true);

        $this->uploadHandlerServiceMock->expects($this->once())
        ->method('deleteSingleFile')
        ->with($this->equalTo('path/to/avatar.jpg'));

        $csrfToken = $this->generateCsrfToken($this->client, 'profil_delete_avatar');

        $route = $this->router->generate('back_profil_delete_avatar');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects($this->router->generate('back_profil'));
        $this->client->followRedirect();

        $this->assertSelectorExists('.fr-alert--success p');
        $this->assertSelectorTextContains('.fr-alert--success p', 'L\'avatar a bien été supprimé.');

        $this->assertNull($this->user->getAvatarFilename());
    }

    public function testDeleteAvatarNoAvatar(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_delete_avatar');

        $route = $this->router->generate('back_profil_delete_avatar');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects($this->router->generate('back_profil'));
        $this->client->followRedirect();

        $this->assertSelectorExists('.fr-alert--error p');
        $this->assertSelectorTextContains('.fr-alert--error p', 'Une erreur est survenue lors de la suppression...');
    }

    public function testEditEmailStep1Success(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[email]' => 'new-email@example.com',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertNotNull($this->user->getEmailAuthCode());
        $this->assertEmailCount(1);
    }

    public function testEditEmailStep1BadFormatEmail(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[email]' => 'new-em',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson(json_encode([
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[email]' => [
                    'errors' => ['Veuillez saisir une adresse email au format adresse@email.fr.'],
                ],
            ],
        ]));
        $this->assertEmailCount(0);
    }

    public function testEditEmailStep1EmptyEmail(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[email]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson(json_encode([
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[email]' => [
                    'errors' => ['Ce champ est obligatoire.'],
                ],
            ],
        ]));
        $this->assertEmailCount(0);
    }

    public function testEditEmailStep1SameEmail(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[email]' => 'admin-01@histologe.fr',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson(json_encode([
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[email]' => [
                    'errors' => ['Veuillez saisir une adresse e-mail différente de l\'actuelle'],
                ],
            ],
        ]));
        $this->assertEmailCount(0);
    }

    public function testEditEmailStep1EmailExistingUser(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[email]' => 'admin-03@histologe.fr',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson(json_encode([
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[email]' => [
                    'errors' => ['Un utilisateur existe déjà avec cette adresse e-mail.'],
                ],
            ],
        ]));
        $this->assertEmailCount(0);
    }

    public function testEditEmailStep1EmailExistingPartner(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[email]' => 'admin@histologe.fr',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson(json_encode([
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[email]' => [
                    'errors' => ['Un partenaire existe déjà avec cette adresse e-mail.'],
                ],
            ],
        ]));
        $this->assertEmailCount(0);
    }

    public function testEditEmailStep2Success(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->user->setEmailAuthCode('123456');
        $this->user->setTempEmail('new-email@example.com');
        $response = $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[code]' => '123456',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson(json_encode([
            'code' => Response::HTTP_OK,
        ]));
    }

    public function testEditEmailStep2InvalidCode(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->user->setEmailAuthCode('123456');
        $response = $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[code]' => 'wrong_code',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson(json_encode([
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[code]' => [
                    'errors' => ['Le code est incorrect.'],
                ],
            ],
        ]));
    }

    public function testEditEmailStep2EmptyCode(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->user->setEmailAuthCode('123456');
        $response = $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[code]' => '',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson(json_encode([
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[code]' => [
                    'errors' => ['Le code est obligatoire.'],
                ],
            ],
        ]));
    }

    public function testEditEmailStep2NoTempEmail(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->user->setEmailAuthCode('123456');
        $this->user->setTempEmail(null);
        $response = $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[code]' => '123456',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson(json_encode([
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[code]' => [
                    'errors' => ['Il n\'y a pas d\'adresse e-mail enregistrée à modifier'],
                ],
            ],
        ]));
    }

    public function testEditEmailStep2EmailExistingUser(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->user->setEmailAuthCode('123456');
        $this->user->setTempEmail('admin-03@histologe.fr');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[code]' => '123456',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson(json_encode([
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[code]' => [
                    'errors' => ['Un utilisateur existe déjà avec cette adresse e-mail.'],
                ],
            ],
        ]));
        $this->assertEmailCount(0);
    }

    public function testEditEmailStep2EmailExistingPartner(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->user->setEmailAuthCode('123456');
        $this->user->setTempEmail('admin@histologe.fr');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[code]' => '123456',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson(json_encode([
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[code]' => [
                    'errors' => ['Un partenaire existe déjà avec cette adresse e-mail.'],
                ],
            ],
        ]));
        $this->assertEmailCount(0);
    }

    public function testEditEmailBadToken(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'wrong_token');

        $route = $this->router->generate('back_profil_edit_email');
        $this->user->setEmailAuthCode('123456');
        $response = $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[email]' => 'new-email@example.com',
            'profil_edit_email[code]' => '123456',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertJson(json_encode([
            'code' => Response::HTTP_UNAUTHORIZED,
            'message' => 'Une erreur s\'est produite. Veuillez actualiser la page.',
        ]));
    }

    public function testEditPasswordSuccess(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_password');

        $route = $this->router->generate('back_profil_edit_password');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'password' => 'NewPassword!123',
            'password-repeat' => 'NewPassword!123',
        ]);
        $this->assertEmailCount(1);

        $this->assertResponseRedirects($this->router->generate('back_profil'));
        $this->client->followRedirect();

        $this->assertSelectorExists('.fr-alert--success p');
        $this->assertSelectorTextContains('.fr-alert--success p', 'Votre mot de passe a bien été modifié.');

        $this->assertTrue($this->client->getContainer()->get('security.password_hasher')
            ->isPasswordValid($this->user, 'NewPassword!123'));
    }

    public function testEditPasswordMismatch(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_password');

        $route = $this->router->generate('back_profil_edit_password');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'password' => 'NewPassword!123',
            'password-repeat' => 'DifferentPassword!456',
        ]);

        $this->assertEmailCount(0);
        $this->assertResponseRedirects($this->router->generate('back_profil'));
        $this->client->followRedirect();

        $this->assertSelectorExists('.fr-alert--error p');
        $this->assertSelectorTextContains('.fr-alert--error p', 'Les mots de passes renseignés doivent être identiques.');
    }

    public function testEditPasswordEmpty(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_password');

        $route = $this->router->generate('back_profil_edit_password');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'password' => '',
            'password-repeat' => '',
        ]);

        $this->assertEmailCount(0);
        $this->assertResponseRedirects($this->router->generate('back_profil'));
        $this->client->followRedirect();

        $this->assertSelectorExists('.fr-alert--error p');
        $this->assertSelectorTextContains('.fr-alert--error p', 'Ce champ est obligatoire.');
    }

    public function testEditPasswordBadFormat(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_password');

        $route = $this->router->generate('back_profil_edit_password');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'password' => 'test',
            'password-repeat' => 'test',
        ]);
        $this->assertEmailCount(0);
        $this->assertResponseRedirects($this->router->generate('back_profil'));
        $this->client->followRedirect();

        $this->assertSelectorExists('.fr-alert--error');
        $this->assertSelectorTextContains('.fr-alert--error', 'Le mot de passe doit contenir au moins 12 caractères.Le mot de passe doit contenir au moins une lettre majuscule.Le mot de passe doit contenir au moins un chiffre.Le mot de passe doit contenir au moins un caractère spécial.');
    }

    public function testEditPasswordEqualToEmail(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_password');

        $route = $this->router->generate('back_profil_edit_password');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'password' => 'admin-01@histologe.fr',
            'password-repeat' => 'admin-01@histologe.fr',
        ]);
        $this->assertEmailCount(0);
        $this->assertResponseRedirects($this->router->generate('back_profil'));
        $this->client->followRedirect();

        $this->assertSelectorExists('.fr-alert--error');
        $this->assertSelectorTextContains('.fr-alert--error', 'Le mot de passe ne doit pas être votre e-mail.');
    }

    public function testEditPasswordBadToken(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'wrong_token');

        $route = $this->router->generate('back_profil_edit_password');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'password' => 'NewPassword!123',
            'password-repeat' => 'NewPassword!123',
        ]);
        $this->assertEmailCount(0);
        $this->assertResponseRedirects($this->router->generate('back_profil'));
        $this->client->followRedirect();

        $this->assertSelectorExists('.fr-alert--error');
        $this->assertSelectorTextContains('.fr-alert--error', 'Une erreur s\'est produite. Veuillez actualiser la page.');
    }
}
