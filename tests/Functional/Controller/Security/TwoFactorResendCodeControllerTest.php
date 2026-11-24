<?php

namespace App\Tests\Functional\Controller\Security;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

class TwoFactorResendCodeControllerTest extends WebTestCase
{
    private const string SUPER_ADMIN_EMAIL = 'admin-01@signal-logement.fr';

    private function loginUntilTwoFactorForm(KernelBrowser $client): void
    {
        // Le limiteur est stocké dans Redis : sans remise à zéro, les exécutions successives
        // de la suite épuisent le quota (3 renvois / 15 min) et font échouer les tests.
        /** @var RateLimiterFactoryInterface $limiterFactory */
        $limiterFactory = static::getContainer()->get('limiter.two_factor_resend_code');
        $limiterFactory->create(self::SUPER_ADMIN_EMAIL.'-127.0.0.1')->reset();

        $crawler = $client->request('GET', '/connexion');
        $client->submit($crawler->filter('form[name="login-form"]')->form([
            'email' => self::SUPER_ADMIN_EMAIL,
            'password' => 'signallogement',
        ]));
        $client->request('GET', '/2fa');
        $this->assertResponseIsSuccessful();
    }

    public function testTwoFactorFormIsAccessible(): void
    {
        $client = static::createClient();
        $this->loginUntilTwoFactorForm($client);

        $this->assertSelectorTextContains('h1', 'Saisissez votre code de vérification');
        $this->assertSelectorTextContains('main p', 'a***@signal-logement.fr');
        $this->assertSelectorExists('#_auth_code[aria-describedby="auth-code-hint"]');
        $this->assertSelectorExists('form[action="/2fa_check"] input[name="_csrf_token"]');
        $this->assertSelectorExists('form[action="/2fa-renvoyer-code"] input[name="_csrf_token"]');
    }

    public function testInvalidCodeIsLinkedToField(): void
    {
        $client = static::createClient();
        $this->loginUntilTwoFactorForm($client);

        $client->submitForm('Valider le code', ['_auth_code' => '000000']);
        $client->followRedirect();

        $this->assertSelectorExists('#_auth_code[aria-invalid="true"][aria-describedby="auth-code-hint auth-code-error"]');
        $this->assertSelectorTextContains('#auth-code-error', 'Le code de vérification n\'est pas valide.');
    }

    public function testResendCodeSendsEmail(): void
    {
        $client = static::createClient();
        $this->loginUntilTwoFactorForm($client);

        $client->submitForm('Renvoyer le code par e-mail');

        $this->assertResponseRedirects('/2fa');
        $this->assertEmailCount(1);
        $client->followRedirect();
        $this->assertSelectorTextContains('.fr-notice--success', 'Le code de vérification vous a été renvoyé par e-mail.');
    }

    public function testResendCodeWithoutCsrfDoesNotSendEmail(): void
    {
        $client = static::createClient();
        $this->loginUntilTwoFactorForm($client);

        $client->request('POST', '/2fa-renvoyer-code');

        $this->assertResponseRedirects('/2fa');
        $this->assertEmailCount(0);
    }

    public function testResendCodeWithoutTwoFactorInProgressIsDenied(): void
    {
        $client = static::createClient();
        $client->request('POST', '/2fa-renvoyer-code');

        $this->assertResponseRedirects();
        $this->assertEmailCount(0);
    }

    public function testTwoFactorSuiviWithoutSessionShowsSessionExpiredPage(): void
    {
        $client = static::createClient();

        foreach (['GET' => '/2fa-suivi', 'POST' => '/2fa-suivi-renvoyer-code'] as $method => $url) {
            $client->request($method, $url);

            // Symfony applique le code de l'AccessDeniedException à la réponse de l'entry point.
            $this->assertResponseStatusCodeSame(403);
            $this->assertSelectorTextContains('.fr-notice--alert', 'Votre session a expiré.');
            $this->assertSelectorExists('a[href$="signalement-et-suivi/suivre-mon-dossier/"]');
        }
        $this->assertEmailCount(0);
    }
}
