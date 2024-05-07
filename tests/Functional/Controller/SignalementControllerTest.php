<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Entity\Suivi;
use App\Manager\UserManager;
use App\Tests\SessionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class SignalementControllerTest extends WebTestCase
{
    use SessionHelper;

    public function provideStatusSignalement(): \Generator
    {
        yield 'Actif' => [Signalement::STATUS_ACTIVE];
        yield 'Clôturé' => [Signalement::STATUS_CLOSED];
        yield 'Refusé' => [Signalement::STATUS_REFUSED];
        yield 'Archivé' => [Signalement::STATUS_ARCHIVED];
    }

    /**
     * @dataProvider provideStatusSignalement
     */
    public function testDisplaySuiviProcedure(int $status): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine');
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => $status,
        ]);
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlSuiviSignalementUser = $router->generate('front_suivi_procedure', [
            'code' => $signalement->getCodeSuivi(),
        ]).'?from='.$signalement->getMailOccupant().'&suiviAuto='.Suivi::ARRET_PROCEDURE;

        $crawler = $client->request('GET', $urlSuiviSignalementUser);
        if (Signalement::STATUS_ARCHIVED === $status) {
            $this->assertResponseRedirects('/');
        } elseif (Signalement::STATUS_ACTIVE === $status) {
            $this->assertEquals('Signalement #2022-1 '.$signalement->getPrenomOccupant().' '.$signalement->getNomOccupant(), $crawler->filter('h1')->eq(2)->text());
        } else {
            $this->assertResponseRedirects(
                '/suivre-mon-signalement/'.$signalement->getCodeSuivi().'?from='.$signalement->getMailOccupant()
            );
        }
    }

    /**
     * @dataProvider provideStatusSignalement
     */
    public function testDisplaySuiviSignalement(int $status): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine');
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => $status,
        ]);
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlSuiviSignalementUser = $router->generate('front_suivi_signalement', [
            'code' => $signalement->getCodeSuivi(),
        ]).'?from='.$signalement->getMailOccupant();

        $crawler = $client->request('GET', $urlSuiviSignalementUser);
        if (Signalement::STATUS_ARCHIVED === $status) {
            $this->assertResponseRedirects('/');
        } elseif (Signalement::STATUS_ACTIVE === $status) {
            $this->assertEquals('Signalement #2022-1 '.$signalement->getPrenomOccupant().' '.$signalement->getNomOccupant(), $crawler->filter('h1')->eq(2)->text());
        } elseif (Signalement::STATUS_CLOSED === $status) {
            $this->assertEquals(
                'Votre signalement a été clôturé, vous ne pouvez plus envoyer de messages.',
                $crawler->filter('.fr-alert--error p')->text()
            );
        } elseif (Signalement::STATUS_REFUSED === $status) {
            $this->assertEquals(
                'Votre signalement a été refusé, vous ne pouvez plus envoyer de messages.',
                $crawler->filter('.fr-alert--error p')->text()
            );
        }
    }

    /**
     * @dataProvider provideStatusSignalement
     */
    public function testPostUsagerResponse(int $status): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine');
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => $status,
        ]);
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlSuiviSignalementUserResponse = $router->generate('front_suivi_signalement_user_response', [
            'code' => $codeSuivi = $signalement->getCodeSuivi(),
        ]);

        $client->request('POST', $urlSuiviSignalementUserResponse, [
            '_token' => $this->generateCsrfToken($client, 'signalement_front_response_'.$signalement->getUuid()),
            'signalement_front_response' => [
                'email' => $emailOccupant = $signalement->getMailOccupant(),
                'type' => UserManager::OCCUPANT,
                'content' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry',
            ],
        ]);
        if (Signalement::STATUS_ACTIVE === $status) {
            $this->assertResponseRedirects('/suivre-mon-signalement/'.$codeSuivi.'?from='.$emailOccupant);
        } else {
            $this->assertResponseRedirects('/');
        }
    }

    public function testPostSignalementDraft(): void
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get(RouterInterface::class);
        $urlPutSignalement = $router->generate('envoi_nouveau_signalement_draft');

        $payloadLocataireSignalement = file_get_contents(__DIR__.'../../../files/post_signalement_draft_payload.json');

        $client->request('POST', $urlPutSignalement, [], [], [], $payloadLocataireSignalement);

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty($response['uuid']);
    }

    /**
     * @dataProvider provideSignalementRequestPayload
     */
    public function testCompleteSignalementDraft(string $path, string $uuidSignalement, int $countEmailSent): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

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

        $arrayResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('uuid', $arrayResponse);
        $this->assertArrayHasKey('signalementReference', $arrayResponse);
        $this->assertArrayHasKey('lienSuivi', $arrayResponse);

        $this->assertEquals($uuidSignalement, $arrayResponse['uuid']);

        $this->assertEmailCount($countEmailSent);

        $signalementDraftRepository = $entityManager->getRepository(SignalementDraft::class);
        $signalementDraft = $signalementDraftRepository->findOneBy(['uuid' => $uuidSignalement]);

        $this->assertEquals(SignalementDraftStatus::EN_SIGNALEMENT, $signalementDraft->getStatus());
        $this->assertEquals(1, $signalementDraft->getSignalements()->count());
    }

    /**
     * @dataProvider provideSignalementDraftUuid
     */
    public function testGetSignalementDraft(string $uuid, string $step): void
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $urlSignalementDraft = $router->generate('informations_signalement_draft', ['uuid' => $uuid]);
        $client->request('GET', $urlSignalementDraft);

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($step, $response['signalement']['payload']['currentStep']);
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

    public function provideSignalementDraftUuid(): \Generator
    {
        yield 'Locataire at informations_complementaires step' => [
            '00000000-0000-0000-2023-locataire001',
            'informations_complementaires',
        ];

        yield 'Bailleur occupant at desordres_batiment_eau step' => [
            '00000000-0000-0000-2023-bailleuroc01',
            'desordres_batiment_eau',
        ];

        yield 'Bailleur at composition_logement step' => [
            '00000000-0000-0000-2023-bailleur0001',
            'composition_logement',
        ];

        yield 'Tiers pro at type_logement step' => [
            '00000000-0000-0000-2023-tierpro00001',
            'type_logement',
        ];

        yield 'Tiers particulier at desordres_logement_aeration step' => [
            '00000000-0000-0000-2023-tierspart001',
            'desordres_logement_aeration',
        ];

        yield 'Service secours at zone_concernee step' => [
            '00000000-0000-0000-2023-secours00001',
            'zone_concernee',
        ];
    }
}
