<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class UserAccountControllerTest extends WebTestCase
{
    public function testActivationUserFormSubmit(): void
    {
        $faker = Factory::create();

        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-02@histologe.fr']);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('activate_account', ['uuid' => $user->getUuid(), 'token' => $user->getToken()]);
        $client->request('GET', $route);

        $password = $faker->password(12);
        $client->submitForm('Confirmer', [
            'password' => $password,
            'password-repeat' => $password,
        ]);

        $this->assertResponseRedirects('/connexion');
    }

    public function testActivationUserFormSubmitWithMismatchedPassword(): void
    {
        $faker = Factory::create();

        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-02@histologe.fr']);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('activate_account', ['uuid' => $user->getUuid(), 'token' => $user->getToken()]);
        $client->request('GET', $route);

        $client->submitForm('Confirmer', [
            'password' => $faker->password(12),
            'password-repeat' => $faker->password(12),
        ]);

        $this->assertSelectorTextContains(
            '.fr-alert.fr-alert--error.fr-alert--sm',
            'Les mots de passe ne correspondent pas.'
        );
    }
}
