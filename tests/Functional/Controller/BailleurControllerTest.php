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
        $route = $this->router->generate('app_bailleur', ['name' => 'habitat', 'postcode' => 13002]);

        $this->client->request('GET', $route);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(19, $response);

        foreach ($response as $item) {
            $this->assertStringContainsString('habitat', strtolower($item['name']));
        }
    }

    public function testSearchTerms(): void
    {
        $routeFirst = $this->router->generate('app_bailleur', ['name' => 'habitat 13', 'postcode' => 13002]);

        $this->client->request('GET', $routeFirst);
        $responseFirst = json_decode($this->client->getResponse()->getContent(), true);

        $routeSecond = $this->router->generate('app_bailleur', ['name' => '13 habitat', 'postcode' => 13002]);
        $this->client->request('GET', $routeSecond);
        $responseSecond = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($responseFirst, $responseSecond);
    }

    public function testSearchBailleursSanitized(): void
    {
        $route = $this->router->generate('app_bailleur', ['name' => 'ra', 'postcode' => 13002, 'sanitize' => true]);

        $this->client->request('GET', $route);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        foreach ($response as $item) {
            $this->assertStringContainsString('ra', strtolower($item['name']));
            $this->assertStringNotContainsString(Bailleur::BAILLEUR_RADIE, strtolower($item['name']));
        }
    }

    public function testSearchBailleursRadies(): void
    {
        $route = $this->router->generate('app_bailleur', ['name' => 'rad', 'postcode' => 13002]);

        $this->client->request('GET', $route);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        foreach ($response as $item) {
            $this->assertStringContainsString(Bailleur::BAILLEUR_RADIE, $item['name']);
        }
    }
}
