<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AffectationUpdateControllerTest extends WebTestCase
{
    /**
     * @dataProvider provideValidTransitionData
     */
    public function testValidWorkflow(string $signalementUuid, array $payload, string $statut, int $mailSent): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy([
            'email' => 'api-01@histologe.fr',
        ]);

        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['uuid' => $signalementUuid]);
        /** @var Collection $affectations */
        $affectations = $signalement->getAffectations();

        /** @var Affectation $affectation */
        $affectation = $affectations->filter(function (Affectation $affectation) {
            return 'Partenaire 13-01' === $affectation->getPartner()->getNom();
        })->current();

        $client->loginUser($user, 'api');
        $client->request(
            method: 'PATCH',
            uri: '/api/affectations/'.$affectation->getUuid(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertResponseIsSuccessful();
        $this->assertEquals($statut, AffectationStatus::mapNewStatus($affectation->getStatut())->value);
        $this->assertEmailCount($mailSent);
    }

    /** @dataProvider provideUnvalidTransitionData */
    public function testInvalidWorkflow(string $signalementUuid, array $payload): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy([
            'email' => 'api-01@histologe.fr',
        ]);

        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['uuid' => $signalementUuid]);
        /** @var Collection $affectations */
        $affectations = $signalement->getAffectations();

        /** @var Affectation $affectation */
        $affectation = $affectations->filter(function (Affectation $affectation) {
            return 'Partenaire 13-01' === $affectation->getPartner()->getNom();
        })->current();

        $client->loginUser($user, 'api');
        $client->request(
            method: 'PATCH',
            uri: '/api/affectations/'.$affectation->getUuid(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Cette transition n\'est pas valide', $response['message']);
    }

    public function provideValidTransitionData(): \Generator
    {
        yield 'NOUVEAU ==> EN_COURS' => [
            '00000000-0000-0000-2022-000000000001',
            [
                'statut' => 'EN_COURS',
            ],
            'EN_COURS',
            0,
        ];
        yield 'EN_COURS ==> FERME' => [
            '00000000-0000-0000-2022-000000000006',
            [
                'statut' => 'FERME',
                'motifCloture' => 'REFUS_DE_VISITE',
                'message' => 'lorem ipsum dolor sit amet',
            ],
            'FERME',
            1,
        ];

        yield 'FERME ==> NOUVEAU' => [
            '00000000-0000-0000-2023-000000000013',
            [
                'statut' => 'NOUVEAU',
                'notifyUsager' => true,
            ],
            'NOUVEAU',
            2,
        ];
    }

    public function provideUnvalidTransitionData(): \Generator
    {
        yield 'NOUVEAU ==> FERME' => [
            '00000000-0000-0000-2022-000000000001',
            [
                'statut' => 'FERME',
                'motifCloture' => 'RELOGEMENT_OCCUPANT',
                'message' => 'Hello world buddy!!!!',
            ],
        ];
        yield 'EN_COURS ==> NOUVEAU' => [
            '00000000-0000-0000-2022-000000000006',
            [
                'statut' => 'NOUVEAU',
                'notifyUsager' => true,
            ],
        ];

        yield 'FERME ==> EN_COURS' => [
            '00000000-0000-0000-2023-000000000013',
            [
                'statut' => 'EN_COURS',
            ],
        ];
    }
}
