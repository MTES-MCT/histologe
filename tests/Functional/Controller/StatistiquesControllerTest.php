<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class StatistiquesControllerTest extends WebTestCase
{
    public function testFrontStatistiques(): void
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request(
            'GET',
            $router->generate(
                'front_statistiques_filter'
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();

        $expectedResponses = [
            ['result' => 1, 'label' => 'count_signalement_resolus'],
            ['result' => 38, 'label' => 'count_signalement'],
        ];

        foreach ($expectedResponses as $expectedResponse) {
            $this->assertEquals($expectedResponse['result'], $response[$expectedResponse['label']]);
        }
        $this->assertEquals($response['count_territory'], \count($response['list_territoires']));
        $this->assertEquals(2, \count($response['signalement_per_desordres_categories']));
        $this->assertArrayHasKey('BATIMENT', $response['signalement_per_desordres_categories']);
        $this->assertArrayHasKey('LOGEMENT', $response['signalement_per_desordres_categories']);
        $this->assertEquals(5, \count($response['signalement_per_logement_desordres']));
        $this->assertEquals(5, \count($response['signalement_per_batiment_desordres']));
    }
}
