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
    public function testCompleteSignalementDraft(string $path, string $uuidSignalement, int $countEmailSent)
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

        $this->assertEmailCount($countEmailSent);
    }

    public function provideSignalementRequestPayload(): \Generator
    {
        yield 'Post signalement as locataire (Mails sent: Occupant + RT)' => [
            'step/validation_signalement/locataire.json',
            '00000000-0000-0000-2023-locataire002',
            2,
        ];

        yield 'Post signalement as bailleur (Mails sent: Occupant + Déclarant + RT)' => [
            'step/validation_signalement/bailleur.json',
            '00000000-0000-0000-2023-bailleur0002',
            3,
        ];

        yield 'Post signalement as service secours (Mails sent: Occupant + RT)' => [
            'step/validation_signalement/service_secours.json',
            '00000000-0000-0000-2023-secours00002',
            2,
        ];

        yield 'Post signalement as bailleur occupant (Mails sent: Occupant + RT)' => [
            'step/validation_signalement/bailleur_occupant.json',
            '00000000-0000-0000-2023-bailleuroc02',
            2,
        ];
    }
}
