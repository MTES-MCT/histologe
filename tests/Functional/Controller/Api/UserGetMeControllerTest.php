<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserGetMeControllerTest extends WebTestCase
{
    /**
     * @dataProvider provideUserEmailApi
     *
     * @param array<int> $expectedPartnerIds
     */
    public function testUserGetMe(string $email, array $expectedPartnerIds, int $nbPartners): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        $client->loginUser($user, 'api');
        $client->request('GET', '/api/users/me');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true);

        $authorizedPartnerIds = array_map(
            fn ($partner) => $partner['id'],
            $response['partenairesAutorises']
        );

        $this->assertCount($nbPartners, $response['partenairesAutorises']);
        foreach ($expectedPartnerIds as $expectedId) {
            $this->assertTrue(
                in_array($expectedId, $authorizedPartnerIds, true),
                "Le partenaire avec l'ID $expectedId n'est pas autorisé."
            );
        }
    }

    public function provideUserEmailApi(): \Generator
    {
        yield 'Partenaire id 2' => ['api-01@signal-logement.fr', [2], 1];
        yield 'Partenaire id 84' => ['api-02@signal-logement.fr', [86], 1];
        yield 'Partenaires EPCI de La Réunion' => ['api-reunion-epci@signal-logement.fr', [19, 21], 2];
        yield 'Partenaires COMMUNE_SCHS' => ['api-oilhi@signal-logement.fr', [6, 12, 15, 22], 31];
        yield 'Partenaires 72 et 74 + partenaires EPCI et CAP_MSA de l\'Hérault' => ['api-34-01@signal-logement.fr', [73, 74, 75, 76], 4];
        yield 'Partenaires du Puy-de-Dôme' => ['api-full-63@signal-logement.fr', [16, 28, 29, 30], 19];
    }
}
