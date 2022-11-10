<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BackPartnerControllerTest extends WebTestCase
{
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

        $client->submitForm('Enregistrer', [
                'partner[nom]' => $faker->company(),
                'partner[email]' => $faker->companyEmail(),
                'partner[isCommune]' => 0,
                'partner[esaboraUrl]' => 'https://api.random-partner.com',
                'partner[esaboraToken]' => 'token',
            ]
        );

        $this->assertResponseRedirects('/bo/partner/');
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

        $crawler = $client->request('POST', $router->generate('back_partner_user_delete', [
            'user' => $userId,
        ]));

        $this->assertResponseRedirects('/bo/partner/');
    }
}
