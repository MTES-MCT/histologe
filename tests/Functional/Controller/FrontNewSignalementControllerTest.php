<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class FrontNewSignalementControllerTest extends WebTestCase
{
    /**
     * @dataProvider provideSignalementRequestPayload
     */
    public function testCompleteSignalementDraft(string $path, string $uuidSignalement)
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get(RouterInterface::class);
        $urlPutSignalement = $router->generate('mise_a_jour_nouveau_signalement_draft', [
            'uuid' => $uuidSignalement,
        ]);

        $payloadLocataireSignalement = file_get_contents(
            __DIR__.'../../../../src/DataFixtures/Files/signalement_draft_payload/'.$path
        );

        $client->request('PUT', $urlPutSignalement, [], [], [], $payloadLocataireSignalement);

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $this->assertEquals(
            ['uuid' => $uuidSignalement],
            json_decode($client->getResponse()->getContent(), true)
        );

        $this->assertEmailCount(2);
    }

    public function provideSignalementRequestPayload(): \Generator
    {
        yield 'Post signalement as locataire' => [
            'step/validation_signalement/locataire.json',
            '00000000-0000-0000-2023-locataire002',
        ];

        yield 'Post signalement as bailleur' => [
            'step/validation_signalement/bailleur.json',
            '00000000-0000-0000-2023-bailleur0002',
        ];
    }
}
