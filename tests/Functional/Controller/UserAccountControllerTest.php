<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class UserAccountControllerTest extends WebTestCase
{
    public function testResetPasswordSuccess(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user->setToken('test-reset-token-valid');
        $user->setTokenExpiredAt(new \DateTimeImmutable('+1 hour'));
        $entityManager->flush();

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->generate('activate_account', ['uuid' => $user->getUuid(), 'token' => 'test-reset-token-valid']);
        $client->request('GET', $route);

        $client->submitForm('Confirmer', [
            'password' => 'NewPassword!123',
            'password-repeat' => 'NewPassword!123',
        ]);

        $this->assertResponseRedirects('/connexion');
        $user = $userRepository->find($user->getId());
        $this->assertTrue(static::getContainer()->get('security.password_hasher')
            ->isPasswordValid($user, 'NewPassword!123'));
    }

    public function testResetPasswordWithSamePassword(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user->setToken('test-reset-token-same');
        $user->setTokenExpiredAt(new \DateTimeImmutable('+1 hour'));
        $entityManager->flush();

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->generate('activate_account', ['uuid' => $user->getUuid(), 'token' => 'test-reset-token-same']);
        $client->request('GET', $route);

        $client->submitForm('Confirmer', [
            'password' => 'signallogement',
            'password-repeat' => 'signallogement',
        ]);

        $this->assertSelectorTextContains(
            '.fr-notice.fr-notice--alert',
            'Votre nouveau mot de passe doit être différent de votre mot de passe actuel.'
        );
    }

    public function testActivationUserFormSubmit(): void
    {
        $faker = Factory::create();

        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-02@signal-logement.fr']);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

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
        self::ensureKernelShutdown();
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
        self::ensureKernelShutdown();
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

        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-02@signal-logement.fr']);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

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

    #[DataProvider('provideInvalidPassword')]
    public function testActivationUserFormSubmitWithInvalidPassword(string $expectedResult, string $password): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-01-02@signal-logement.fr']);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

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

    public static function provideInvalidPassword(): \Generator
    {
        yield 'blank' => ['Cette valeur ne doit pas être vide', ''];
        yield 'short' => ['Le mot de passe doit contenir au moins 12 caractères', 'short'];
        yield 'no_uppercase' => ['Le mot de passe doit contenir au moins une lettre majuscule', 'nouppercase'];
        yield 'no_lowercase' => ['Le mot de passe doit contenir au moins une lettre minuscule', 'NOLOWERCASE'];
        yield 'no_digit' => ['Le mot de passe doit contenir au moins un chiffre', 'NoDigitNoDigit'];
        yield 'no_special' => ['Le mot de passe doit contenir au moins un caractère spécial', 'NoSpecial'];
    }
}
