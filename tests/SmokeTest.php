<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class SmokeTest extends WebTestCase
{
    /**
     * @dataProvider provideRoutes
     */
    public function testPageSuccessfullyRespondWithoutError500WithAnonymousUser(string $path, int $statusCode): void
    {
        $client = static::createClient();
        $client->request('GET', $path);

        $this->assertLessThan(
            $statusCode,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
    }

    /**
     * @dataProvider provideRoutes
     */
    public function testPageSuccessfullyRespondWithoutError500WithAdminTerritoire(string $path, int $statusCode): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-69-mdl@histologe.fr']);
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', $path);

        $this->assertLessThan(
            $statusCode,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
    }

    /**
     * @dataProvider provideRoutes
     */
    public function testPageSuccessfullyRespondWithoutError500WithUtilisateurPartenaire(string $path, int $statusCode): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-69-05@histologe.fr']);
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', $path);

        $this->assertLessThan(
            $statusCode,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
    }

    /**
     * @dataProvider provideRoutes
     */
    public function testPageSuccessfullyRespondWithoutError500WithSuperAdmin(string $path, int $statusCode): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', $path);

        $this->assertLessThan(
            $statusCode,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
    }

    public function provideRoutes(): \Generator
    {
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $routes = $router->getRouteCollection();

        /** @var Route $route */
        foreach ($routes as $route) {
            if (([] === $route->getMethods() ||
                (1 === \count($route->getMethods())) && \in_array('GET', $route->getMethods())) &&
                !\in_array($route->getPath(), ['/error-504', '/error-502'])
            ) {
                $path = $route->getPath();
                yield $path => [$path, 500];
            }
        }
    }
}
