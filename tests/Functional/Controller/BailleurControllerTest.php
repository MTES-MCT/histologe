<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Bailleur;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BailleurControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /* @var RouterInterface $router */
        $this->router = self::getContainer()->get(RouterInterface::class);
    }

    public function testSearchBailleurs(): void
    {
        $route = $this->router->generate('app_bailleur', ['name' => 'habitat', 'inseecode' => 13002]);

        $this->client->request('GET', $route);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertCount(19, $response);

        foreach ($response as $item) {
            $this->assertStringContainsString('habitat', strtolower($item['name']));
        }
    }

    public function testSearchTerms(): void
    {
        $routeFirst = $this->router->generate('app_bailleur', ['name' => 'habitat 13', 'inseecode' => 13002]);

        $this->client->request('GET', $routeFirst);
        $responseFirst = json_decode((string) $this->client->getResponse()->getContent(), true);

        $routeSecond = $this->router->generate('app_bailleur', ['name' => '13 habitat', 'inseecode' => 13002]);
        $this->client->request('GET', $routeSecond);
        $responseSecond = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertEquals($responseFirst, $responseSecond);
    }

    public function testSearchBailleursSanitized(): void
    {
        $route = $this->router->generate('app_bailleur', ['name' => 'IÉ', 'inseecode' => 30100, 'sanitize' => true]);

        $this->client->request('GET', $route);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        foreach ($response as $item) {
            $this->assertStringContainsString('ie', strtolower($item['name']));
            $this->assertStringNotContainsString(Bailleur::BAILLEUR_RADIE, strtolower($item['name']));
        }
    }

    public function testSearchBailleursRadies(): void
    {
        $route = $this->router->generate('app_bailleur', ['name' => 'rad', 'inseecode' => 13002]);

        $this->client->request('GET', $route);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);

        foreach ($response as $item) {
            $this->assertStringContainsString(Bailleur::BAILLEUR_RADIE, $item['name']);
        }
    }

    public function testSearchTermsSanitized(): void
    {
        $routeFirst = $this->router->generate('app_bailleur', ['name' => 'habitat 13', 'inseecode' => 13002, 'sanitize' => true]);

        $this->client->request('GET', $routeFirst);
        $responseFirst = json_decode((string) $this->client->getResponse()->getContent(), true);

        $routeSecond = $this->router->generate('app_bailleur', ['name' => '13 habitat', 'inseecode' => 13002, 'sanitize' => true]);
        $this->client->request('GET', $routeSecond);
        $responseSecond = json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertEquals($responseFirst, $responseSecond);
    }
}
