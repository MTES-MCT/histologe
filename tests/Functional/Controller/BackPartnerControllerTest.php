<?php

namespace App\Tests\Functional\Controller;

use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BackPartnerControllerTest extends WebTestCase
{
    use SessionHelper;

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
            'Enregistrer',
            [
                'partner[nom]' => $faker->company(),
                'partner[email]' => $faker->companyEmail(),
                'partner[isCommune]' => 0,
                'partner[esaboraUrl]' => 'https://api.random-partner.com',
                'partner[esaboraToken]' => 'token',
            ]
        );

        $this->assertResponseRedirects('/bo/partenaires/');
    }

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
            ['user_transfer' => ['user' => $userId, 'partner' => $newPartnerId], '_token' => $this->generateCsrfToken($client, 'partner_user_transfer')]
        );

        $this->assertEquals($newPartnerId, $user->getPartner()->getId());
        $this->assertResponseRedirects('/bo/partenaires/'.$newPartnerId.'/editer');
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
            ['user_transfer' => ['user' => $userId, 'partner' => $newPartnerId], '_token' => $this->generateCsrfToken($client, 'partner_user_transfer')]
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
            ['user_transfer' => ['user' => $userId, 'partner' => $newPartnerId], '_token' => $this->generateCsrfToken($client, 'bad_csrf')]
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
