<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FrontStatistiquesControllerTest extends WebTestCase
{
    public function testSignalementSuccessfullyDisplay()
    {
        $client = static::createClient();

        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $route = $generatorUrl->generate('front_statistiques');
        $client->request('GET', $route);
        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
    }
}
