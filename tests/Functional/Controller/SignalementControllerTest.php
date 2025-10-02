<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Entity\Suivi;
use App\Repository\SuiviRepository;
use App\Tests\SessionHelper;
use App\Tests\UserHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class SignalementControllerTest extends WebTestCase
{
    use SessionHelper;
    use UserHelper;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    public function provideStatusSignalement(): \Generator
    {
        yield 'Actif' => [SignalementStatus::ACTIVE->value];
        yield 'Fermé' => [SignalementStatus::CLOSED->value];
        yield 'Refusé' => [SignalementStatus::REFUSED->value];
        yield 'Archivé' => [SignalementStatus::ARCHIVED->value];
        yield 'Brouillon' => [SignalementStatus::DRAFT->value];
        yield 'Brouillon de signalement' => [SignalementStatus::DRAFT_ARCHIVED->value];
        yield 'En médiation' => [SignalementStatus::EN_MEDIATION->value];
    }

    /**
     * @dataProvider provideStatusSignalement
     */
    public function testDisplaySuiviProcedure(string $status): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine');
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => $status,
            'isUsagerAbandonProcedure' => null,
        ]);
        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlSuiviProcedureUser = $router->generate('front_suivi_procedure', [
            'code' => $signalement->getCodeSuivi(),
        ]).'?suiviAuto='.Suivi::ARRET_PROCEDURE;

        $client->request('GET', $urlSuiviProcedureUser);

        if (in_array($status, [SignalementStatus::DRAFT->value, SignalementStatus::DRAFT_ARCHIVED->value])) {
            $this->assertResponseRedirects('/authentification/'.$signalement->getCodeSuivi());
        } elseif (SignalementStatus::ARCHIVED->value === $status) {
            $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi());
        } else {
            $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/procedure');
        }
    }

    /**
     * @dataProvider provideStatusSignalement
     */
    public function testDisplaySuiviSignalement(string $status): void
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
        $urlSuiviSignalementUser = $router->generate('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);

        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');
        $crawler = $client->request('GET', $urlSuiviSignalementUser);

        if (SignalementStatus::ARCHIVED->value === $status) {
            $this->assertResponseStatusCodeSame(200);
            $this->assertEquals(
                'Votre signalement a été archivé, vous ne pouvez plus envoyer de messages.',
                $crawler->filter('.fr-tile__detail')->text()
            );
        } elseif (SignalementStatus::ACTIVE->value === $status || SignalementStatus::EN_MEDIATION->value === $status) {
            $this->assertEquals('Votre dossier', $crawler->filter('h1')->text());
        } elseif (SignalementStatus::CLOSED->value === $status) {
            $this->assertEquals(
                'Votre message suite à la clôture de votre dossier a bien été envoyé. Vous ne pouvez désormais plus envoyer de messages.',
                $crawler->filter('.fr-tile__detail')->text()
            );
        } elseif (SignalementStatus::REFUSED->value === $status) {
            $this->assertEquals(
                'Signalement refusé',
                $crawler->filter('.fr-badge.fr-badge--sm.fr-badge--no-icon.fr-badge--error')->text()
            );
        } else {
            $this->assertResponseRedirects('/authentification/'.$signalement->getCodeSuivi());
        }
    }

    public function testSuiviSignalementProcedure(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine');
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => SignalementStatus::ACTIVE,
            'isUsagerAbandonProcedure' => 0,
        ]);
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlSuiviSignalementUserResponse = $router->generate('front_suivi_signalement_procedure', ['code' => $signalement->getCodeSuivi()]);
        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        $crawler = $client->request('POST', $urlSuiviSignalementUserResponse);
        $this->assertEquals('Demander l\'arrêt de la procédure', $crawler->filter('h1')->text());
    }

    public function testSuiviSignalementProcedureAbandon(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine');
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => SignalementStatus::ACTIVE,
            'isUsagerAbandonProcedure' => 0,
        ]);
        $this->assertFalse($signalement->getIsUsagerAbandonProcedure());
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlSuiviSignalementUserResponse = $router->generate('front_suivi_signalement_procedure_abandon', ['code' => $codeSuivi = $signalement->getCodeSuivi()]);

        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        $reason = 'Changement de logement';
        $details = 'on a trouvé un meilleur appartement';
        $client->request('POST', $urlSuiviSignalementUserResponse, [
            'usager_cancel_procedure' => [
                'reason' => $reason,
                'details' => $details,
                '_token' => $this->generateCsrfToken($client, 'usager_cancel_procedure'),
            ],
        ]);
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());
        $this->assertResponseRedirects('/suivre-mon-signalement/'.$codeSuivi);
        $this->assertTrue($signalement->getIsUsagerAbandonProcedure());
        /** @var Suivi $lastSuivi */
        $lastSuivi = $signalement->getSuivis()->last();
        $this->assertStringContainsString($signalementUser->getUser()->getNomComplet(), $lastSuivi->getDescription());
        $this->assertStringContainsString('souhaite fermer son dossier', $lastSuivi->getDescription());
        $this->assertStringContainsString('pour le motif suivant : '.$reason, $lastSuivi->getDescription());
        $this->assertStringContainsString('arrêt de procédure : '.$details, $lastSuivi->getDescription());
    }

    public function testSuiviSignalementProcedurePoursuite(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine');
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => SignalementStatus::ACTIVE,
            'isUsagerAbandonProcedure' => null,
        ]);
        $this->assertNull($signalement->getIsUsagerAbandonProcedure());
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlSuiviSignalementUserResponse = $router->generate('front_suivi_signalement_procedure_poursuite', ['code' => $codeSuivi = $signalement->getCodeSuivi()]);

        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        $details = 'on veut vraiment vivre mieux';
        $client->request('POST', $urlSuiviSignalementUserResponse, [
            'usager_poursuivre_procedure' => [
                'details' => $details,
                '_token' => $this->generateCsrfToken($client, 'usager_poursuivre_procedure'),
            ],
        ]);
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());
        $this->assertResponseRedirects('/suivre-mon-signalement/'.$codeSuivi);
        $this->assertFalse($signalement->getIsUsagerAbandonProcedure());
        /** @var Suivi $lastSuivi */
        $lastSuivi = $signalement->getSuivis()->last();
        $this->assertStringContainsString($signalementUser->getUser()->getNomComplet(), $lastSuivi->getDescription());
        $this->assertStringContainsString('vouloir poursuivre la procédure', $lastSuivi->getDescription());
        $this->assertStringContainsString('Commentaire : '.$details, $lastSuivi->getDescription());
    }

    /**
     * @dataProvider provideStatusSignalement
     */
    public function testPostUsagerResponse(string $status): void
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
        $urlSuiviSignalementUserResponse = $router->generate('front_suivi_signalement_messages', ['code' => $codeSuivi = $signalement->getCodeSuivi()]);

        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        $crawler = $client->request('POST', $urlSuiviSignalementUserResponse, [
            'message_usager' => [
                'description' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry',
                '_token' => $this->generateCsrfToken($client, 'message_usager'),
            ],
        ]);
        if (SignalementStatus::ACTIVE->value === $status) {
            $this->assertResponseRedirects('/suivre-mon-signalement/'.$codeSuivi.'/messages');
            $nbSuiviMessageUsager = self::getContainer()->get(SuiviRepository::class)->count(['category' => SuiviCategory::MESSAGE_USAGER, 'signalement' => $signalement]);
            $this->assertEquals(1, $nbSuiviMessageUsager);
        } elseif (SignalementStatus::REFUSED->value === $status) {
            $this->assertEquals('Votre signalement a été refusé, vous ne pouvez plus envoyer de messages.', $crawler->filter('.fr-alert p')->text());
        } elseif (SignalementStatus::ARCHIVED->value === $status) {
            $this->assertEquals('Votre signalement a été archivé, vous ne pouvez plus envoyer de messages.', $crawler->filter('.fr-alert p')->text());
        } elseif (SignalementStatus::CLOSED->value === $status) {
            $this->assertEquals('Votre message suite à la clôture de votre dossier a bien été envoyé. Vous ne pouvez désormais plus envoyer de messages.', $crawler->filter('.fr-alert p')->text());
        } elseif (SignalementStatus::EN_MEDIATION->value === $status) {
            $this->assertEquals('Votre dossier est en médiation, vous ne pouvez pas envoyer de messages.', $crawler->filter('.fr-alert p')->text());
        } else {
            $this->assertResponseRedirects('/authentification/'.$signalement->getCodeSuivi());
        }
    }

    public function testUsagerAddDocuments(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2025-09']);

        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        $fileRepository = $entityManager->getRepository(File::class);
        $files = $fileRepository->findBy([], [], 2);
        foreach ($files as $file) {
            $file->setSignalement($signalement);
            $file->setUploadedBy($signalementUser->getUser());
            $file->setIsTemp(true);
        }
        $entityManager->flush();

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get(RouterInterface::class);
        $urlAddDocuments = $router->generate('front_suivi_signalement_documents', ['code' => $signalement->getCodeSuivi()]);

        $client->request('POST', $urlAddDocuments, [
            'form' => [
                'file' => [
                    $files[0]->getId(),
                    $files[1]->getId(),
                ],
                '_token' => $this->generateCsrfToken($client, 'form'),
            ],
        ]);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/documents');

        $flashBag = $client->getRequest()->getSession()->getFlashBag(); // @phpstan-ignore-line
        $this->assertTrue($flashBag->has('success'));
        $successMessages = $flashBag->get('success');
        $this->assertEquals('Vos documents ont bien été enregistrés.', $successMessages[0]);

        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2025-09']);
        $lastSuivi = $signalement->getLastSuivi();
        $this->assertEquals(count($lastSuivi->getSuiviFiles()), 2);
        $this->assertStringStartsWith('L&#039;occupant a ajouté des documents.', $lastSuivi->getDescription());
    }

    public function testUsagerAddInvalidDocuments(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2025-09']);

        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        $fileRepository = $entityManager->getRepository(File::class);
        $files = $fileRepository->findBy([], [], 2);

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get(RouterInterface::class);
        $urlAddDocuments = $router->generate('front_suivi_signalement_documents', ['code' => $signalement->getCodeSuivi()]);

        $client->request('POST', $urlAddDocuments, [
            'form' => [
                'file' => [
                    $files[0]->getId(),
                    $files[1]->getId(),
                ],
                '_token' => $this->generateCsrfToken($client, 'form'),
            ],
        ]);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/documents');

        $flashBag = $client->getRequest()->getSession()->getFlashBag(); // @phpstan-ignore-line
        $this->assertFalse($flashBag->has('success'));
    }

    public function testPostSignalementDraft(): void
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get(RouterInterface::class);
        $urlPutSignalement = $router->generate('envoi_formulaire_signalement_draft');

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
        $urlPutSignalement = $router->generate('mise_a_jour_formulaire_signalement_draft', [
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

    public function testUpdateSignalementDraftArchived(): void
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get(RouterInterface::class);
        $urlPutSignalement = $router->generate('mise_a_jour_formulaire_signalement_draft', [
            'uuid' => '00000000-0000-0000-2024-locataire003',
        ]);

        $payloadLocataireSignalement = file_get_contents(
            __DIR__.'../../../files/post_signalement_draft_payload.json'
        );

        $client->request('PUT', $urlPutSignalement, [], [], [], $payloadLocataireSignalement);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
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

    public function testSignalementEdit(): void
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $urlSignalementEdit = $router->generate('front_formulaire_signalement_edit', ['uuid' => 'test']);
        $client->request('GET', $urlSignalementEdit);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());

        $urlSignalementEdit = $router->generate('front_formulaire_signalement_edit', ['uuid' => '00000000-0000-0000-2024-locataire003']);
        $client->request('GET', $urlSignalementEdit);

        $this->assertEquals(Response::HTTP_FOUND, $client->getResponse()->getStatusCode());
        $this->assertResponseRedirects('/signalement');

        $urlSignalementEdit = $router->generate('front_formulaire_signalement_edit', ['uuid' => '00000000-0000-0000-2023-locataire001']);
        $client->request('GET', $urlSignalementEdit);

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testSendMailContinueFromNotValidDraft(): void
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get(RouterInterface::class);
        $url = $router->generate('send_mail_continue_from_draft');

        $client->request('POST', $url, [], [], [], '');

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
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
