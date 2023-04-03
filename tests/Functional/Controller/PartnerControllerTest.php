<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\PartnerType;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class PartnerControllerTest extends WebTestCase
{
    use SessionHelper;

    public function testPartnersSuccessfullyDisplay()
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_partner_index');
        $client->request('GET', $route);

        $this->assertResponseIsSuccessful();
    }

    public function testPartnersExperimentalTerritorySuccessfullyDisplay()
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-63-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_partner_index');
        $client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains(
            '.fr-display-inline-table',
            'Compétences',
        );
    }

    public function testPartnerFormSubmit(): void
    {
        $faker = Factory::create();

        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_partner_new');
        $client->request('GET', $route);

        $client->submitForm(
            'Créer le partenaire',
            [
                'partner[territory]' => 1,
                'partner[nom]' => $faker->company(),
                'partner[email]' => $faker->companyEmail(),
                'partner[type]' => PartnerType::ARS->value,
                'partner[insee]' => [],
            ]
        );

        $this->assertResponseRedirects('/bo/partenaires/');
    }
    // TODO : faire un test avec un type nécessitant esabora et codes insee
    // TODO : tester les nouvelles routes (view, adduser, edituser)

    public function testTransferUserAccount(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
        $client->loginUser($admin);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $user = $userRepository->findOneBy(['email' => 'user-13-02@histologe.fr']);
        $userId = $user->getId();

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = static::getContainer()->get(PartnerRepository::class);
        $newPartnerId = $partnerRepository->findOneBy(['nom' => 'Partenaire 13-01'])->getId();

        $client->request(
            'POST',
            $router->generate('back_partner_user_transfer'),
            [
                'user_transfer' => ['user' => $userId, 'partner' => $newPartnerId],
                '_token' => $this->generateCsrfToken($client, 'partner_user_transfer'),
            ]
        );

        $this->assertEquals($newPartnerId, $user->getPartner()->getId());
        $this->assertResponseRedirects('/bo/partenaires/'.$newPartnerId.'/voir');
    }

    public function testTransferUserAccountWithUserNotAllowed(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'user-13-01@histologe.fr']);
        $client->loginUser($admin);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $user = $userRepository->findOneBy(['email' => 'user-13-02@histologe.fr']);
        $userId = $user->getId();
        $userOldPartner = $user->getPartner()->getId();

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = static::getContainer()->get(PartnerRepository::class);
        $newPartnerId = $partnerRepository->findOneBy(['nom' => 'Partenaire 13-02'])->getId();

        $client->request(
            'POST',
            $router->generate('back_partner_user_transfer'),
            [
                'user_transfer' => ['user' => $userId, 'partner' => $newPartnerId],
                '_token' => $this->generateCsrfToken($client, 'partner_user_transfer'),
            ]
        );

        $this->assertEquals($userOldPartner, $user->getPartner()->getId());
    }

    public function testTransferUserAccountWithCsrfUnvalid(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
        $client->loginUser($admin);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $user = $userRepository->findOneBy(['email' => 'user-13-02@histologe.fr']);
        $userId = $user->getId();
        $userOldPartner = $user->getPartner()->getId();

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = static::getContainer()->get(PartnerRepository::class);
        $newPartnerId = $partnerRepository->findOneBy(['nom' => 'Partenaire 13-02'])->getId();

        $client->request(
            'POST',
            $router->generate('back_partner_user_transfer'),
            [
                'user_transfer' => ['user' => $userId, 'partner' => $newPartnerId],
                '_token' => $this->generateCsrfToken($client, 'bad_csrf'),
            ]
        );

        $this->assertEquals($userOldPartner, $user->getPartner()->getId());
        $this->assertResponseRedirects('/bo/partenaires/');
    }

    public function testDeleteUserAccount(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($admin);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $user = $userRepository->findOneBy(['email' => 'user-974-01@histologe.fr']);
        $userId = $user->getId();

        $client->request('POST', $router->generate('back_partner_user_delete'), [
            'user_id' => $userId,
            '_token' => $this->generateCsrfToken($client, 'partner_user_delete'),
        ]);

        $this->assertEquals(2, $user->getStatut());
    }

    public function testDeleteUserAccountWithCsrfUnvalid(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($admin);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $user = $userRepository->findOneBy(['email' => 'user-01-03@histologe.fr']);
        $userId = $user->getId();

        $client->request('POST', $router->generate('back_partner_user_delete'), [
            'user_id' => $userId,
            '_token' => $this->generateCsrfToken($client, 'bad_csrf'),
        ]);

        $this->assertNotEquals(2, $user->getStatut());
        $this->assertResponseRedirects('/bo/partenaires/');
    }
}
