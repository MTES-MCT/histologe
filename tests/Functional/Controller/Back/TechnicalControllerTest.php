<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Back;

use App\Entity\User;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class TechnicalControllerTest extends WebTestCase
{
    private ?string $technicalRouteEnabled = null;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->technicalRouteEnabled = $_ENV['TECHNICAL_ROUTE_ENABLED'] ?? null;
    }

    protected function tearDown(): void
    {
        if (null === $this->technicalRouteEnabled) {
            unset($_ENV['TECHNICAL_ROUTE_ENABLED']);
        } else {
            $_ENV['TECHNICAL_ROUTE_ENABLED'] = $this->technicalRouteEnabled;
        }

        parent::tearDown();
    }

    #[DataProvider('providePhpInfosAccess')]
    public function testPhpInfosAccess(string $enabled, ?string $email, int $expectedStatusCode): void
    {
        $_ENV['TECHNICAL_ROUTE_ENABLED'] = $enabled;
        $client = static::createClient();

        if (null !== $email) {
            $userRepository = static::getContainer()->get(UserRepository::class);
            $user = $userRepository->findOneBy(['email' => $email]);
            self::assertInstanceOf(User::class, $user);
            $client->loginUser($user);
        }

        $router = static::getContainer()->get(RouterInterface::class);
        $client->request('GET', $router->generate('back_technical_phpinfos'));

        self::assertResponseStatusCodeSame($expectedStatusCode);

        if (Response::HTTP_OK === $expectedStatusCode) {
            $content = $client->getResponse()->getContent();
            self::assertIsString($content);
            self::assertStringContainsString('PHP Version', $content);
        }

        if (null === $email) {
            self::assertResponseRedirects($router->generate('app_login'));
        }
    }

    /**
     * @return \Generator<string, array{string, ?string, int}>
     */
    public static function providePhpInfosAccess(): \Generator
    {
        yield 'disabled even for ROLE_ADMIN' => ['0', 'admin-01@signal-logement.fr', Response::HTTP_NOT_FOUND];
        yield 'enabled for ROLE_ADMIN' => ['1', 'admin-01@signal-logement.fr', Response::HTTP_OK];
        yield 'enabled denied for ROLE_ADMIN_TERRITORY' => ['1', 'admin-territoire-13-01@signal-logement.fr', Response::HTTP_FORBIDDEN];
        yield 'enabled denied for ROLE_ADMIN_PARTNER' => ['1', 'admin-partenaire-13-01@signal-logement.fr', Response::HTTP_FORBIDDEN];
        yield 'enabled denied for ROLE_USER_PARTNER' => ['1', 'user-13-01@signal-logement.fr', Response::HTTP_FORBIDDEN];
        yield 'enabled redirects anonymous user to login' => ['1', null, Response::HTTP_FOUND];
    }
}
