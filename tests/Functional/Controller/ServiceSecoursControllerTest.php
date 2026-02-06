<?php

namespace App\Tests\Functional\Controller;

use App\Repository\ServiceSecoursRouteRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class ServiceSecoursControllerTest extends WebTestCase
{
    public function testRoutes(): void
    {
        $client = static::createClient();
        /** @var ServiceSecoursRouteRepository $serviceSecoursRouteRepository */
        $serviceSecoursRouteRepository = static::getContainer()->get(ServiceSecoursRouteRepository::class);
        $routes = $serviceSecoursRouteRepository->findAll();
        $this->assertCount(2, $routes);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        foreach ($routes as $route) {
            // OK
            $url = $router->generate('service_secours_index', [
                'name' => $route->getName(),
                'uuid' => $route->getUuid(),
                'domain' => 'localhost',
            ]);
            $client->request('GET', $url);
            $this->assertResponseIsSuccessful();

            $manifestUrl = $router->generate('service_secours_webmanifest', [
                'name' => $route->getName(),
                'uuid' => $route->getUuid(),
                'domain' => 'localhost',
            ]);
            $client->request('GET', $manifestUrl);
            $this->assertResponseIsSuccessful();
            $this->assertResponseHeaderSame('Content-Type', 'application/manifest+json');
            $content = $client->getResponse()->getContent();
            $this->assertIsString($content);
            $this->assertJson($content);
            // KO
            $url = $router->generate('service_secours_index', [
                'name' => $route->getName().'1',
                'uuid' => $route->getUuid(),
                'domain' => 'localhost',
            ]);
            $client->request('GET', $url);
            $this->assertResponseStatusCodeSame(404);

            $url = $router->generate('service_secours_index', [
                'name' => $route->getName(),
                'uuid' => $route->getUuid().'1',
                'domain' => 'localhost',
            ]);
            $client->request('GET', $url);
            $this->assertResponseStatusCodeSame(404);
        }
    }
}
