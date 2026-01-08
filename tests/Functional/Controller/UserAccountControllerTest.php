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
        $user = $userRepository->findOneBy(['email' => 'user-01-02@signal-logement.fr']);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('activate_account', ['uuid' => $user->getUuid(), 'token' => $user->getToken()]);
        $client->request('GET', $route);

        $password = $faker->password(12).'Aa1@';
        $client->submitForm('Confirmer', [
            'password' => $password,
            'password-repeat' => $password,
        ]);

        $this->assertResponseRedirects('/connexion');
    }

    public function testUserLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/connexion');

        $client->submitForm('Se connecter', [
            'email' => 'user-01-01@signal-logement.fr',
            'password' => 'signallogement',
        ]);

        $this->assertResponseRedirects('/bo/?mesDossiersMessagesUsagers=1&mesDossiersAverifier=1&mesDossiersActiviteRecente=1');
    }

    public function testUserApiLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/connexion');

        $client->submitForm('Se connecter', [
            'email' => 'api-02@signal-logement.fr',
            'password' => 'signallogement',
        ]);

        $this->assertResponseRedirects('/connexion');
    }

    public function testActivationUserFormSubmitWithMismatchedPassword(): void
    {
        $faker = Factory::create();

        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-02@signal-logement.fr']);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('activate_account', ['uuid' => $user->getUuid(), 'token' => $user->getToken()]);
        $client->request('GET', $route);

        $client->submitForm('Confirmer', [
            'password' => $faker->password(12),
            'password-repeat' => $faker->password(12),
        ]);

        $this->assertSelectorTextContains(
            '.fr-notice.fr-notice--alert',
            'Les mots de passe ne correspondent pas.'
        );
    }

    /**
     * @dataProvider provideInvalidPassword
     */
    public function testActivationUserFormSubmitWithInvalidPassword(string $expectedResult, string $password): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-02@signal-logement.fr']);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('activate_account', ['uuid' => $user->getUuid(), 'token' => $user->getToken()]);
        $client->request('GET', $route);

        $client->submitForm('Confirmer', [
            'password' => $password,
            'password-repeat' => $password,
        ]);

        $this->assertSelectorTextContains(
            '.fr-notice.fr-notice--alert',
            $expectedResult
        );
    }

    public function provideInvalidPassword(): \Generator
    {
        yield 'blank' => ['Cette valeur ne doit pas être vide', ''];
        yield 'short' => ['Le mot de passe doit contenir au moins 12 caractères', 'short'];
        yield 'no_uppercase' => ['Le mot de passe doit contenir au moins une lettre majuscule', 'nouppercase'];
        yield 'no_lowercase' => ['Le mot de passe doit contenir au moins une lettre minuscule', 'NOLOWERCASE'];
        yield 'no_digit' => ['Le mot de passe doit contenir au moins un chiffre', 'NoDigitNoDigit'];
        yield 'no_special' => ['Le mot de passe doit contenir au moins un caractère spécial', 'NoSpecial'];
    }
}
