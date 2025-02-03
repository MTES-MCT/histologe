<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class PartnerControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private PartnerRepository $partnerRepository;
    private RouterInterface $router;
    private $faker;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->router = self::getContainer()->get(RouterInterface::class);
        $this->faker = Factory::create();
        $this->partnerRepository = static::getContainer()->get(PartnerRepository::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
        $this->client->loginUser($user);
    }

    public function testPartnersSuccessfullyDisplay()
    {
        $route = $this->router->generate('back_partner_index');
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
    }

    public function testPartnersExperimentalTerritorySuccessfullyDisplay()
    {
        $route = $this->router->generate('back_partner_index');
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains(
            '.fr-table__wrapper',
            'Compétences',
        );
    }

    public function testPartnerFormSubmit(): void
    {
        $route = $this->router->generate('back_partner_new');
        $this->client->request('GET', $route);

        $this->client->submitForm(
            'Créer le partenaire',
            [
                'partner[territory]' => 1,
                'partner[nom]' => $this->faker->company(),
                'partner[email]' => $this->faker->companyEmail(),
                'partner[type]' => PartnerType::ARS->value,
            ]
        );

        $this->assertResponseRedirects('/bo/partenaires/');
    }

    public function testPartnerSuccessfullyDisplay()
    {
        $partner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-01']);

        $route = $this->router->generate('back_partner_view', ['id' => $partner->getId()]);
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
    }

    public function testPartnerEditFormSubmit(): void
    {
        /** @var Partner $partner */
        $partner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-02']);

        $route = $this->router->generate('back_partner_edit', ['id' => $partner->getId()]);
        $this->client->request('GET', $route);

        $this->client->submitForm(
            'Enregistrer',
            [
                'partner[territory]' => 1,
                'partner[nom]' => $this->faker->company(),
                'partner[email]' => $this->faker->companyEmail(),
                'partner[type]' => PartnerType::ARS->value,
            ]
        );

        $this->assertResponseRedirects('/bo/partenaires/'.$partner->getId().'/voir');
    }

    public function testDeletePartner()
    {
        /** @var Partner $partner */
        $partner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-01']);
        $partnerUsers = $partner->getUsers();
        $mailBeforArchive = $partner->getEmail();

        $route = $this->router->generate('back_partner_delete');
        $this->client->request(
            'POST',
            $route,
            [
                'partner_id' => $partner->getId(),
                '_token' => $this->generateCsrfToken($this->client, 'partner_delete'),
            ]
        );

        $this->assertResponseRedirects('/bo/partenaires/');
        $this->assertTrue($partner->getIsArchive());
        $this->assertStringStartsWith($mailBeforArchive.User::SUFFIXE_ARCHIVED, $partner->getEmail());
        foreach ($partnerUsers as $user) {
            if ('admin-partenaire-multi-ter-13-01@histologe.fr' === $user->getEmail()) {
                $this->assertEquals(User::STATUS_ACTIVE, $user->getStatut());
                $this->assertCount(1, $user->getPartners());
            } else {
                $this->assertEquals(User::STATUS_ARCHIVE, $user->getStatut());
                $this->assertStringContainsString(User::SUFFIXE_ARCHIVED, $user->getEmail());
            }
        }
    }

    /**
     * @dataProvider provideAgentEmailToAddOnPartner
     */
    public function testAddNewAgentToPartner(string $email, string $expected)
    {
        $feature_multi_territories = static::getContainer()->getParameter('feature_multi_territories');
        if (!$feature_multi_territories) {
            $this->markTestSkipped('La fonctionnalité "feature_multi_territories" est désactivée.');
        }
        /** @var Partner $partner */
        $partner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-03']);

        $route = $this->router->generate('back_partner_add_user', ['id' => $partner->getId()]);
        $this->client->request(
            'POST',
            $route,
            [
                'user_partner' => [
                    'email' => $email,
                    'role' => 'ROLE_USER_PARTNER',
                    'prenom' => 'John',
                    'nom' => 'Doe',
                    'isMailingActive' => 0,
                    '_token' => $this->generateCsrfToken($this->client, 'user_partner'),
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        if ('redirect' === $expected) {
            $this->assertArrayHasKey('redirect', $response);
            $this->assertArrayHasKey('url', $response);
            $this->assertStringEndsWith('/bo/partenaires/'.$partner->getId().'/voir#agents', $response['url']);
            $this->assertEmailCount(1);
        } else {
            $this->assertArrayHasKey('content', $response);
            $this->assertStringContainsString($expected, $response['content']);
        }
    }

    public function provideAgentEmailToAddOnPartner(): \Generator
    {
        yield 'Invalid email' => ['nanana', 'L&#039;adresse e-mail est invalide.'];
        yield 'Partner email already exists' => ['partenaire-13-02@histologe.fr', 'Un partenaire existe déjà avec cette adresse e-mail.'];
        yield 'Email already exists on same territory' => ['user-13-01@histologe.fr', 'Un utilisateur avec cette adresse e-mail existe déja sur le territoire.'];
        yield 'Email existing on RT' => ['admin-territoire-01-01@histologe.fr', 'Un utilisateur Responsable Territoire existe déjà avec cette adresse e-mail.'];
        yield 'Email existing on SA' => ['admin-01@histologe.fr', 'Un utilisateur Super Admin existe déjà avec cette adresse e-mail.'];
        yield 'Email existing with permission affectation' => ['user-partenaire-30@histologe.fr', 'Un utilisateur ayant les droits d&#039;affectation existe déjà avec cette adresse e-mail.'];

        yield 'New user' => ['new.email@test.com', 'redirect'];
        yield 'New user from usager' => ['usager-01@histologe.fr', 'redirect'];
        yield 'Email ok to multi territories' => ['user-44-02@histologe.fr', 'Ce compte agent existe déjà dans :'];
    }

    /**
     * @dataProvider provideMultiTerAgentEmailToAddOnPartner
     */
    public function testAddExistingAgentToPartner(string $email, string $expected)
    {
        $feature_multi_territories = static::getContainer()->getParameter('feature_multi_territories');
        if (!$feature_multi_territories) {
            $this->markTestSkipped('La fonctionnalité "feature_multi_territories" est désactivée.');
        }
        /** @var Partner $partner */
        $partner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-03']);
        $route = $this->router->generate('back_partner_add_user_multi', ['id' => $partner->getId()]);
        $this->client->request(
            'POST',
            $route,
            [
                'user_partner_email' => [
                    'email' => $email,
                    '_token' => $this->generateCsrfToken($this->client, 'user_partner_email'),
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        if ('redirect' === $expected) {
            $this->assertArrayHasKey('redirect', $response);
            $this->assertArrayHasKey('url', $response);
            $this->assertStringEndsWith('/bo/partenaires/'.$partner->getId().'/voir#agents', $response['url']);
            $this->assertEmailCount(1);
        } else {
            $this->assertArrayHasKey('content', $response);
            $this->assertStringContainsString($expected, $response['content']);
        }
    }

    public function provideMultiTerAgentEmailToAddOnPartner(): \Generator
    {
        yield 'Invalid email' => ['nanana', 'L&#039;adresse e-mail est invalide.'];
        yield 'Partner email already exists' => ['partenaire-13-02@histologe.fr', 'Un partenaire existe déjà avec cette adresse e-mail.'];
        yield 'Email already exists on same territory' => ['user-13-01@histologe.fr', 'Un utilisateur avec cette adresse e-mail existe déja sur le territoire.'];
        yield 'Email existing on RT' => ['admin-territoire-01-01@histologe.fr', 'Un utilisateur Responsable Territoire existe déjà avec cette adresse e-mail.'];
        yield 'Email existing on SA' => ['admin-01@histologe.fr', 'Un utilisateur Super Admin existe déjà avec cette adresse e-mail.'];
        yield 'Email existing with permission affectation' => ['user-partenaire-30@histologe.fr', 'Un utilisateur ayant les droits d&#039;affectation existe déjà avec cette adresse e-mail.'];

        yield 'New user' => ['new.email@test.com', 'Agent introuvable avec cette adresse e-mail.'];
        yield 'New user from usager' => ['usager-01@histologe.fr', 'Agent introuvable avec cette adresse e-mail.'];
        yield 'Email ok to multi territories' => ['user-44-02@histologe.fr', 'redirect'];
    }

    public function testAddUserToPartner()
    {
        $feature_multi_territories = static::getContainer()->getParameter('feature_multi_territories');
        if ($feature_multi_territories) {
            $this->markTestSkipped('La fonctionnalité "feature_multi_territories" est activée.');
        }

        /** @var Partner $partner */
        $partner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-03']);

        $route = $this->router->generate('back_partner_user_add', ['id' => $partner->getId()]);
        $this->client->request(
            'POST',
            $route,
            [
                'user_create' => [
                    'roles' => 'ROLE_USER_PARTNER',
                    'prenom' => 'John',
                    'nom' => 'Doe',
                    'email' => 'ajout.partner@histologe.fr',
                    'isMailingActive' => false,
                ],
                '_token' => $this->generateCsrfToken($this->client, 'partner_user_create'),
            ]
        );

        $this->assertResponseRedirects('/bo/partenaires/'.$partner->getId().'/voir#agents');
        $this->assertEmailCount(1);
    }

    public function testEditRoleOfUserOfPartner()
    {
        /** @var User $partnerUser */
        $partnerUser = $this->userRepository->findOneBy(['email' => 'user-13-01@histologe.fr']);
        $partner = $partnerUser->getPartners()->first();

        $route = $this->router->generate('back_partner_user_edit', ['partner' => $partner->getId(), 'user' => $partnerUser->getId()]);
        $this->client->request(
            'POST',
            $route,
            [
                'user_partner' => [
                    'role' => 'ROLE_ADMIN_TERRITORY',
                    'prenom' => 'John',
                    'nom' => 'Doe',
                    'email' => 'user-13-01@histologe.fr',
                    'isMailingActive' => 0,
                    '_token' => $this->generateCsrfToken($this->client, 'user_partner'),
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertContains('ROLE_ADMIN_TERRITORY', $partnerUser->getRoles());
    }

    /**
     * @dataProvider provideAgentEmailToEdit
     */
    public function testEditUserOfPartner(string $email, string $expected, int $nbEmailSent)
    {
        /** @var User $partnerUser */
        $partnerUser = $this->userRepository->findOneBy(['email' => 'user-13-01@histologe.fr']);
        $partner = $partnerUser->getPartners()->first();

        $route = $this->router->generate('back_partner_user_edit', ['partner' => $partner->getId(), 'user' => $partnerUser->getId()]);
        $this->client->request(
            'POST',
            $route,
            [
                'user_partner' => [
                    'role' => 'ROLE_USER_PARTNER',
                    'prenom' => 'John',
                    'nom' => 'Doe',
                    'email' => $email,
                    'isMailingActive' => 0,
                    '_token' => $this->generateCsrfToken($this->client, 'user_partner'),
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        if ('redirect' === $expected) {
            $this->assertArrayHasKey('redirect', $response);
            $this->assertArrayHasKey('url', $response);
            $this->assertStringEndsWith('/bo/partenaires/'.$partner->getId().'/voir#agents', $response['url']);
            $this->assertEmailCount($nbEmailSent);
        } else {
            $this->assertArrayHasKey('content', $response);
            $this->assertStringContainsString($expected, $response['content']);
            $this->assertEmailCount($nbEmailSent);
        }
    }

    public function provideAgentEmailToEdit(): \Generator
    {
        yield 'Invalid email' => ['nanana', 'L&#039;adresse e-mail est invalide.', 0];
        yield 'Partner email already exists' => ['partenaire-13-02@histologe.fr', 'Un partenaire existe déjà avec cette adresse e-mail.', 0];
        yield 'User email already' => ['user-44-02@histologe.fr', 'Un utilisateur existe déjà avec cette adresse e-mail.', 0];

        yield 'Original email' => ['user-13-01@histologe.fr', 'redirect', 0];
        yield 'Changed email' => ['new.email@test.com', 'redirect', 1];
    }

    public function testEditAnonymizedUser()
    {
        /** @var User $partnerUser */
        $partnerUser = $this->userRepository->findAnonymizedUsers()[0];
        $partner = $partnerUser->getPartners()->first();

        $route = $this->router->generate('back_partner_user_edit', ['partner' => $partner->getId(), 'user' => $partnerUser->getId()]);
        $this->client->request(
            'POST',
            $route,
            [
                'user_partner' => [
                    'role' => 'ROLE_USER_PARTNER',
                    'prenom' => 'John',
                    'nom' => 'Doe',
                    'email' => 'ajout.partner@histologe.fr',
                    'isMailingActive' => 0,
                    '_token' => $this->generateCsrfToken($this->client, 'user_partner'),
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTransferUserAccount(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-02@histologe.fr']);
        $partner = $user->getPartners()->first();
        $userId = $user->getId();

        $newPartnerId = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-01'])->getId();

        $this->client->request(
            'POST',
            $this->router->generate('back_partner_user_transfer', ['id' => $partner->getId()]),
            [
                'user_transfer' => ['user' => $userId, 'partner' => $newPartnerId],
                '_token' => $this->generateCsrfToken($this->client, 'partner_user_transfer'),
            ]
        );

        $this->assertEquals($newPartnerId, $user->getPartners()->first()->getId());
        $this->assertResponseRedirects('/bo/partenaires/'.$newPartnerId.'/voir#agents');
    }

    public function testTransferUserAccountWithUserNotAllowed(): void
    {
        $admin = $this->userRepository->findOneBy(['email' => 'user-13-01@histologe.fr']);
        $this->client->loginUser($admin);

        $user = $this->userRepository->findOneBy(['email' => 'user-13-02@histologe.fr']);
        $userId = $user->getId();
        $userOldPartner = $user->getPartners()->first()->getId();

        $newPartnerId = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-02'])->getId();

        $this->client->request(
            'POST',
            $this->router->generate('back_partner_user_transfer', ['id' => $user->getPartners()->first()->getId()]),
            [
                'user_transfer' => ['user' => $userId, 'partner' => $newPartnerId],
                '_token' => $this->generateCsrfToken($this->client, 'partner_user_transfer'),
            ]
        );

        $this->assertEquals($userOldPartner, $user->getPartners()->first()->getId());
    }

    public function testTransferUserAccountWithCsrfUnvalid(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-02@histologe.fr']);
        $userId = $user->getId();
        $userOldPartner = $user->getPartners()->first()->getId();

        $newPartnerId = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-02'])->getId();

        $this->client->request(
            'POST',
            $this->router->generate('back_partner_user_transfer', ['id' => $user->getPartners()->first()->getId()]),
            [
                'user_transfer' => ['user' => $userId, 'partner' => $newPartnerId],
                '_token' => $this->generateCsrfToken($this->client, 'bad_csrf'),
            ]
        );

        $this->assertEquals($userOldPartner, $user->getPartners()->first()->getId());
        $this->assertResponseRedirects('/bo/partenaires/');
    }

    public function testDeleteUserAccount(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@histologe.fr']);
        $userId = $user->getId();

        $this->client->request('POST', $this->router->generate('back_partner_user_delete', ['id' => $user->getPartners()->first()->getId()]), [
            'user_id' => $userId,
            '_token' => $this->generateCsrfToken($this->client, 'partner_user_delete'),
        ]);

        $this->assertEquals(2, $user->getStatut());
        $this->assertStringContainsString(User::SUFFIXE_ARCHIVED, $user->getEmail());
        $this->assertEmailCount(1);
    }

    public function testDeleteMultiUserFromPartner(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-partenaire-multi-ter-13-01@histologe.fr']);
        $userId = $user->getId();

        $this->client->request('POST', $this->router->generate('back_partner_user_delete', ['id' => $user->getPartners()->first()->getId()]), [
            'user_id' => $userId,
            '_token' => $this->generateCsrfToken($this->client, 'partner_user_delete'),
        ]);

        $this->assertEquals(1, $user->getStatut());
        $this->assertEmailCount(1);
        $this->assertCount(1, $user->getPartners());
    }

    public function testDeleteAnonymizedUserAccount(): void
    {
        $user = $this->userRepository->findAnonymizedUsers()[0];
        $userId = $user->getId();

        $this->client->request('POST', $this->router->generate('back_partner_user_delete', ['id' => $user->getPartners()->first()->getId()]), [
            'user_id' => $userId,
            '_token' => $this->generateCsrfToken($this->client, 'partner_user_delete'),
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteUserAccountWithCsrfUnvalid(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-01-03@histologe.fr']);
        $userId = $user->getId();

        $this->client->request('POST', $this->router->generate('back_partner_user_delete', ['id' => $user->getPartners()->first()->getId()]), [
            'user_id' => $userId,
            '_token' => $this->generateCsrfToken($this->client, 'bad_csrf'),
        ]);

        $this->assertNotEquals(2, $user->getStatut());
        $this->assertStringNotContainsString(User::SUFFIXE_ARCHIVED, $user->getEmail());
        $this->assertResponseRedirects('/bo/partenaires/');
    }

    public function testCheckMailOk()
    {
        $route = $this->router->generate('back_partner_check_user_email');
        $this->client->request(
            'POST',
            $route,
            [
                'email' => 'paul@yopmail.com',
                '_token' => $this->generateCsrfToken($this->client, 'partner_checkmail'),
            ]
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('{"success":"email_ok"}', $this->client->getResponse()->getContent());
    }

    public function testCheckMailNotValid()
    {
        $route = $this->router->generate('back_partner_check_user_email');
        $this->client->request(
            'POST',
            $route,
            [
                'email' => 'paul@yopmail.f',
                '_token' => $this->generateCsrfToken($this->client, 'partner_checkmail'),
            ]
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('{"error":"L\u0027adresse e-mail est invalide"}', $this->client->getResponse()->getContent());
    }

    public function testCheckMailAlreadyExists()
    {
        $route = $this->router->generate('back_partner_check_user_email');
        $this->client->request(
            'POST',
            $route,
            [
                'email' => 'admin-01@histologe.fr',
                '_token' => $this->generateCsrfToken($this->client, 'partner_checkmail'),
            ]
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('{"error":"Un utilisateur existe d\u00e9j\u00e0 avec cette adresse e-mail."}', $this->client->getResponse()->getContent());
    }
}
