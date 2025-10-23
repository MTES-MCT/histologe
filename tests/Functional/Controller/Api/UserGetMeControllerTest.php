<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserGetMeControllerTest extends WebTestCase
{
    /**
     * @dataProvider provideUserEmailApi
     */
    public function testUserGetMe(string $email, int $nbPartners): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        $client->loginUser($user, 'api');
        $client->request('GET', '/api/users/me');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode((string) $client->getResponse()->getContent(), true);

        foreach ($response['partenairesAutorises'] as $partner) {
            $this->assertArrayHasKey('uuid', $partner);
        }

        $this->assertCount($nbPartners, $response['partenairesAutorises']);
    }

    public function provideUserEmailApi(): \Generator
    {
        yield 'Partenaire id 2' => ['api-01@signal-logement.fr', 3];
        yield 'Partenaire id 84' => ['api-02@signal-logement.fr', 1];
        yield 'Partenaires EPCI de La Réunion' => ['api-reunion-epci@signal-logement.fr', 2];
        yield 'Partenaires COMMUNE_SCHS' => ['api-oilhi@signal-logement.fr', 31];
        yield 'Partenaires 72 et 74 + partenaires EPCI et CAP_MSA de l\'Hérault' => ['api-34-01@signal-logement.fr', 4];
        yield 'Partenaires du Puy-de-Dôme' => ['api-full-63@signal-logement.fr', 19];
    }
}
