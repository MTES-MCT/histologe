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
    private UploadHandlerService&MockObject $uploadHandlerServiceMock;
    private FileScanner&MockObject $fileScannerMock;
    private ImageManipulationHandler&MockObject $imageManipulationHandlerMock;

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

        $this->user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($this->user);
    }

    public function testIndex(): void
    {
        $route = $this->router->generate('back_profil');
        $crawler = $this->client->request('GET', $route);
        $this->assertResponseIsSuccessful();

        $h1Elements = $crawler->filter('h1');
        $this->assertStringContainsString('Mon profil', $h1Elements->eq(4)->text());
    }

    public function testEditInfosSuccess(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'user_profil_info');

        $route = $this->router->generate('back_profil_edit_infos');
        $this->client->request('POST', $route, [
            'user_profil_info' => [
                'prenom' => 'John',
                'nom' => 'Doe',
                'fonction' => 'Directeur',
                'phone' => '0123456789',
                '_token' => $csrfToken,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson((string) json_encode(['code' => Response::HTTP_OK]));
        $this->assertEquals('Doe', $this->user->getNom());
        $this->assertEquals('John', $this->user->getPrenom());
        $this->assertEquals('Directeur', $this->user->getFonction());
        $this->assertEquals('0123456789', $this->user->getPhone());
    }

    public function testEditInfosWithAvatarSuccess(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'user_profil_info');

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
        ->method('moveFilePath');
        $this->imageManipulationHandlerMock->expects($this->once())
        ->method('avatar');

        $route = $this->router->generate('back_profil_edit_infos');
        $this->client->request('POST', $route, [
            'user_profil_info' => [
                'prenom' => 'John',
                'nom' => 'Doe',
                'fonction' => 'Directeur',
                'phone' => '',
                '_token' => $csrfToken,
            ],
        ], ['user_profil_info' => [
            'avatar' => $imageFile,
        ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson((string) json_encode(['code' => Response::HTTP_OK]));
        $this->assertEquals('Doe', $this->user->getNom());
        $this->assertEquals('John', $this->user->getPrenom());
        $this->assertEquals('Directeur', $this->user->getFonction());
        $this->assertEquals('avatarTitle.jpg', $this->user->getAvatarFilename());
    }

    public function testEditInfosWithAvatarNotClean(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'user_profil_info');

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
            'user_profil_info' => [
                'prenom' => 'John',
                'nom' => 'Doe',
                'fonction' => '',
                'phone' => '',
                '_token' => $csrfToken,
            ],
        ], ['user_profil_info' => [
            'avatar' => $imageFile,
        ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertEquals('Le fichier est infecté', $response['errors']['user_profil_info[avatar]']['errors'][0]);
    }

    public function testEditInfosWithAvatarBadFormat(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'user_profil_info');

        $imageFile = new UploadedFile(
            __DIR__.'/../../../files/sample.pdf',
            'sample.pdf',
            'application/pdf',
            null,
            true
        );

        $route = $this->router->generate('back_profil_edit_infos');
        $this->client->request('POST', $route, [
            'user_profil_info' => [
                'prenom' => 'John',
                'nom' => 'Doe',
                'fonction' => '',
                'phone' => '',
                '_token' => $csrfToken,
            ],
        ], ['user_profil_info' => [
            'avatar' => $imageFile,
        ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertEquals('Veuillez télécharger une image valide (JPEG, PNG ou GIF)', $response['errors']['user_profil_info[avatar]']['errors'][0]);
    }

    public function testEditInfosInvalidCsrfToken(): void
    {
        $route = $this->router->generate('back_profil_edit_infos');

        $this->client->request('POST', $route, [
            'user_profil_info' => [
                'prenom' => 'John',
                'nom' => 'Doe',
                'fonction' => '',
                'phone' => '',
                '_token' => 'invalid_csrf_token',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testEditInfosInvalidData(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'user_profil_info');

        $route = $this->router->generate('back_profil_edit_infos');

        $this->client->request('POST', $route, [
            'user_profil_info' => [
                'prenom' => '',
                'nom' => '',
                'fonction' => '',
                'phone' => '',
                '_token' => $csrfToken,
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertEquals('Merci de saisir un prénom.', $response['errors']['user_profil_info[prenom]']['errors'][0]);
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

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertArrayHasKey('closeModal', $response);
        $this->assertArrayHasKey('htmlTargetContents', $response);
        $this->assertTrue($response['stayOnPage']);
        $this->assertTrue($response['closeModal']);
        $msgFlash = 'L\'avatar a bien été supprimé.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);

        $this->assertNull($this->user->getAvatarFilename());
    }

    public function testDeleteAvatarNoAvatar(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_delete_avatar');

        $route = $this->router->generate('back_profil_delete_avatar');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
        ]);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertTrue($response['stayOnPage']);
        $msgFlash = 'Une erreur est survenue lors de la suppression, veuillez réessayer.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
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
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertEquals('Veuillez saisir une adresse e-mail au format adresse@email.fr.', $response['errors']['profil_edit_email[email]']['errors'][0]);
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
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $expectedJson = [
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[email]' => [
                    'errors' => ['Ce champ est obligatoire.'],
                ],
            ],
        ];
        $this->assertEquals($expectedJson, $response);
        $this->assertEmailCount(0);
    }

    public function testEditEmailStep1SameEmail(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[email]' => 'admin-01@signal-logement.fr',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertEquals('Veuillez saisir une adresse e-mail différente de l\'actuelle', $response['errors']['profil_edit_email[email]']['errors'][1]);
        $this->assertEmailCount(0);
    }

    public function testEditEmailStep1EmailExistingUser(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[email]' => 'admin-03@signal-logement.fr',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $expectedJson = [
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[email]' => [
                    'errors' => ['Un utilisateur existe déjà avec cette adresse e-mail.'],
                ],
            ],
        ];
        $this->assertEquals($expectedJson, $response);
        $this->assertEmailCount(0);
    }

    public function testEditEmailStep1EmailExistingPartner(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[email]' => 'admin@signal-logement.fr',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $expectedJson = [
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[email]' => [
                    'errors' => ['Un partenaire existe déjà avec cette adresse e-mail.'],
                ],
            ],
        ];
        $this->assertEquals($expectedJson, $response);
        $this->assertEmailCount(0);
    }

    public function testEditEmailStep2Success(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->user->setEmailAuthCode('123456');
        $this->user->setTempEmail('new-email@example.com');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[code]' => '123456',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
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
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertEquals('Le code est incorrect.', $response['errors']['profil_edit_email[code]']['errors'][1]);
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
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertEquals('Le code est obligatoire.', $response['errors']['profil_edit_email[code]']['errors'][1]);
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
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertEquals('Il n\'y a pas d\'adresse e-mail enregistrée à modifier', $response['errors']['profil_edit_email[code]']['errors'][1]);
    }

    public function testEditEmailStep2EmailExistingUser(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->user->setEmailAuthCode('123456');
        $this->user->setTempEmail('admin-03@signal-logement.fr');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[code]' => '123456',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $expectedJson = [
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[code]' => [
                    'errors' => ['Un utilisateur existe déjà avec cette adresse e-mail.'],
                ],
            ],
        ];
        $this->assertEquals($expectedJson, $response);
        $this->assertEmailCount(0);
    }

    public function testEditEmailStep2EmailExistingPartner(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_email');

        $route = $this->router->generate('back_profil_edit_email');
        $this->user->setEmailAuthCode('123456');
        $this->user->setTempEmail('admin@signal-logement.fr');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'profil_edit_email[code]' => '123456',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $expectedJson = [
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'profil_edit_email[code]' => [
                    'errors' => ['Un partenaire existe déjà avec cette adresse e-mail.'],
                ],
            ],
        ];
        $this->assertEquals($expectedJson, $response);
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
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertTrue($response['stayOnPage']);
        $msgFlash = 'Une erreur s\'est produite. Veuillez actualiser la page.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
    }

    public function testEditPasswordSuccess(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_password');

        $route = $this->router->generate('back_profil_edit_password');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'password-current' => 'signallogement',
            'password' => 'NewPassword!123',
            'password-repeat' => 'NewPassword!123',
        ]);
        $this->assertEmailCount(1);

        $this->assertResponseIsSuccessful();
        $this->assertJson((string) json_encode(['code' => Response::HTTP_OK]));

        $this->assertTrue($this->client->getContainer()->get('security.password_hasher')
            ->isPasswordValid($this->user, 'NewPassword!123'));
    }

    public function testEditPasswordMismatch(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_password');

        $route = $this->router->generate('back_profil_edit_password');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'password-current' => 'signallogement',
            'password' => 'NewPassword!123',
            'password-repeat' => 'DifferentPassword!456',
        ]);

        $this->assertEmailCount(0);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $expectedJson = [
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'password-repeat' => [
                    'errors' => ['Les mots de passes renseignés doivent être identiques.'],
                ],
            ],
        ];
        $this->assertEquals($expectedJson, $response);
    }

    public function testEditPasswordEmpty(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_password');

        $route = $this->router->generate('back_profil_edit_password');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'password-current' => 'signallogement',
            'password' => '',
            'password-repeat' => '',
        ]);

        $this->assertEmailCount(0);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertEquals('Ce champ est obligatoire.', $response['errors']['password']['errors'][0]);
    }

    public function testEditPasswordBadFormat(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_password');

        $route = $this->router->generate('back_profil_edit_password');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'password-current' => 'signallogement',
            'password' => 'test',
            'password-repeat' => 'test',
        ]);
        $this->assertEmailCount(0);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertEquals('Le mot de passe doit contenir au moins 12 caractères.', $response['errors']['password']['errors'][0]);
    }

    public function testEditPasswordEqualToEmail(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_password');

        $route = $this->router->generate('back_profil_edit_password');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'password-current' => 'signallogement',
            'password' => 'admin-01@signal-logement.fr',
            'password-repeat' => 'admin-01@signal-logement.fr',
        ]);
        $this->assertEmailCount(0);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertEquals('Le mot de passe ne doit pas être votre e-mail.', $response['errors']['password']['errors'][0]);
    }

    public function testEditPasswordBadToken(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'wrong_token');

        $route = $this->router->generate('back_profil_edit_password');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'password-current' => 'signallogement',
            'password' => 'NewPassword!123',
            'password-repeat' => 'NewPassword!123',
        ]);
        $this->assertEmailCount(0);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertTrue($response['stayOnPage']);
        $msgFlash = 'Une erreur s\'est produite. Veuillez actualiser la page.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
    }

    public function testEditPasswordBadCurrentPassword(): void
    {
        $csrfToken = $this->generateCsrfToken($this->client, 'profil_edit_password');

        $route = $this->router->generate('back_profil_edit_password');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'password-current' => 'incorrect-password',
            'password' => 'NewPassword!123',
            'password-repeat' => 'NewPassword!123',
        ]);
        $this->assertEmailCount(0);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $expectedJson = [
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'password-current' => [
                    'errors' => ['Le mot de passe ne correspond pas à celui enregistré.'],
                ],
            ],
        ];
        $this->assertEquals($expectedJson, $response);
    }

    public function testEditNotificationEmailError(): void
    {
        $route = $this->router->generate('back_profil');
        $crawler = $this->client->request('GET', $route);
        $form = $crawler->filter('#notification_email_form')->form();
        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $expectedJson = [
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => [
                'isMailingSummary' => [
                    'errors' => ['Merci de choisir une option de notification.'],
                ],
            ],
        ];
        $this->assertEquals($expectedJson, $response);
    }

    public function testEditNotificationEmailSuccess(): void
    {
        $route = $this->router->generate('back_profil');
        $crawler = $this->client->request('GET', $route);
        $form = $crawler->filter('#notification_email_form')->form();

        $form->setValues([
            'isMailingSummary' => 0,
        ]);

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertFalse($user->getIsMailingSummary());
        $this->assertTrue($user->getIsMailingActive());
    }

    public function testSubscribeAndUnsubscribeRT(): void
    {
        $this->user = $this->userRepository->findOneBy(['email' => 'admin-territoire-13-02@signal-logement.fr']);
        $this->client->loginUser($this->user);

        // subscribe on all active signalement in territory
        $token = $this->generateCsrfToken($this->client, 'subscriptions_changes');
        $route = $this->router->generate('profil_subscriptions_changes').'?_token='.$token;
        $this->client->request('GET', $route);

        $this->assertResponseRedirects($this->router->generate('back_profil'));
        $this->assertEquals(21, $this->user->getUserSignalementSubscriptions()->count());

        // unsubscribe on all signalement in territory
        $route .= '&action=unsubscribe';
        $this->client->request('GET', $route);
        $this->assertResponseRedirects($this->router->generate('back_profil'));
        $this->user = $this->userRepository->findOneBy(['email' => 'admin-territoire-13-02@signal-logement.fr']);
        $this->assertEquals(0, $this->user->getUserSignalementSubscriptions()->count());
    }

    public function testUnsubscribeAndSubscribeAgent(): void
    {
        $this->user = $this->userRepository->findOneBy(['email' => 'user-partenaire-30@signal-logement.fr']);
        $this->client->loginUser($this->user);

        // unsubscribe on all active signalement without interractions
        $token = $this->generateCsrfToken($this->client, 'subscriptions_changes');
        $route = $this->router->generate('profil_subscriptions_changes').'?_token='.$token;
        $this->client->request('GET', $route.'&action=unsubscribe');

        $this->assertResponseRedirects($this->router->generate('back_profil'));
        $this->assertEquals(0, $this->user->getUserSignalementSubscriptions()->count());

        // subscribe on all signalement for my partner
        $this->client->request('GET', $route);
        $this->assertResponseRedirects($this->router->generate('back_profil'));
        $this->user = $this->userRepository->findOneBy(['email' => 'user-partenaire-30@signal-logement.fr']);
        $this->assertEquals(1, $this->user->getUserSignalementSubscriptions()->count());
    }

    public function testUnsubscribeAgentWhenNoOtherSubscription(): void
    {
        $this->user = $this->userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $this->client->loginUser($this->user);

        $token = $this->generateCsrfToken($this->client, 'subscriptions_changes');
        $route = $this->router->generate('profil_subscriptions_changes').'?_token='.$token.'&action=unsubscribe';
        $this->client->request('GET', $route);

        $this->assertResponseRedirects($this->router->generate('back_profil'));
        $this->assertEquals(4, $this->user->getUserSignalementSubscriptions()->count());
    }
}
