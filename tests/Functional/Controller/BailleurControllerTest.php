<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BailleurControllerTest extends WebTestCase
{
    public function testSearchBailleurs(): void
    {
        $client = static::createClient();
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('app_bailleur', ['name' => 'habitat', 'postcode' => 13002]);

        $client->request('GET', $route);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(19, $response);

        foreach ($response as $item) {
            $this->assertStringContainsString('habitat', strtolower($item['name']));
        }
    }

    public function testSearchTerms(): void
    {
        $client = static::createClient();
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $routeFirst = $router->generate('app_bailleur', ['name' => 'habitat 13', 'postcode' => 13002]);

        $client->request('GET', $routeFirst);
        $responseFirst = json_decode($client->getResponse()->getContent(), true);

        $routeSecond = $router->generate('app_bailleur', ['name' => '13 habitat', 'postcode' => 13002]);
        $client->request('GET', $routeSecond);
        $responseSecond = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($responseFirst, $responseSecond);
    }
}
