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
        $partner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-03']);
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
            $this->assertEquals(User::STATUS_ARCHIVE, $user->getStatut());
            $this->assertStringContainsString(User::SUFFIXE_ARCHIVED, $user->getEmail());
        }
    }

    public function testAddUserToPartner()
    {
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

    public function testEditUserOfPartner()
    {
        /** @var User $partnerUser */
        $partnerUser = $this->userRepository->findOneBy(['email' => 'user-13-01@histologe.fr']);
        $partner = $partnerUser->getPartners()->first();

        $route = $this->router->generate('back_partner_user_edit', ['id' => $partner->getId()]);
        $this->client->request(
            'POST',
            $route,
            [
                'user_edit' => [
                    'roles' => 'ROLE_USER_PARTNER',
                    'prenom' => 'John',
                    'nom' => 'Doe',
                    'email' => 'ajout.partner@histologe.fr',
                    'isMailingActive' => false,
                ],
                'user_id' => $partnerUser->getId(),
                '_token' => $this->generateCsrfToken($this->client, 'partner_user_edit'),
            ]
        );

        $this->assertResponseRedirects('/bo/partenaires/'.$partner->getId().'/voir#agents');
        $this->assertEmailCount(1);
    }

    public function testEditAnonymizedUser()
    {
        /** @var User $partnerUser */
        $partnerUser = $this->userRepository->findAnonymizedUsers()[0];
        $partner = $partnerUser->getPartners()->first();

        $route = $this->router->generate('back_partner_user_edit', ['id' => $partner->getId()]);
        $this->client->request(
            'POST',
            $route,
            [
                'user_edit' => [
                    'roles' => 'ROLE_USER_PARTNER',
                    'prenom' => 'John',
                    'nom' => 'Doe',
                    'email' => 'ajout.partner@histologe.fr',
                    'isMailingActive' => false,
                ],
                'user_id' => $partnerUser->getId(),
                '_token' => $this->generateCsrfToken($this->client, 'partner_user_edit'),
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
