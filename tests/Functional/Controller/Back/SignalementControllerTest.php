<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Tag;
use App\Repository\SignalementRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class SignalementControllerTest extends WebTestCase
{
    use SessionHelper;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    /**
     * @dataProvider provideRoutes
     */
    public function testSignalementSuccessfullyDisplay(string $route, Signalement $signalement): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        $client->loginUser($user);
        $client->request('GET', $route);
        switch ($signalement->getStatut()) {
            case SignalementStatus::ARCHIVED:
                $this->assertResponseRedirects('/bo/signalements/');
                break;
            case SignalementStatus::DRAFT:
            case SignalementStatus::DRAFT_ARCHIVED:
                $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
                break;
            default:
                $this->assertResponseIsSuccessful((string) $signalement->getId());
                $this->assertSelectorTextContains(
                    'h1.fr-h2',
                    '#'.$signalement->getReference(),
                    $signalement->getReference()
                );
        }
    }

    public function provideRoutes(): \Generator
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        $signalements = $signalementRepository->findAll();

        /** @var Signalement $signalement */
        foreach ($signalements as $signalement) {
            $route = $generatorUrl->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);
            yield $route => [$route, $signalement];
        }
    }

    /**
     * @dataProvider provideRoleSignalementRoutes
     */
    public function testButtonsDisplayedByRole(string $email, string $uuid, string $elementSelector = '', string $elementText = ''): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        $user = $userRepository->findOneBy(['email' => $email]);
        $route = $generatorUrl->generate('back_signalement_view', ['uuid' => $uuid]);

        $client->loginUser($user);
        $client->request('GET', $route);
        $this->assertSelectorTextContains(
            $elementSelector,
            $elementText
        );
    }

    public function provideRoleSignalementRoutes(): \Generator
    {
        yield 'SA - Nouveau' => [
            'admin-01@signal-logement.fr',
            '00000000-0000-0000-2025-000000000001',
            '#open-accept-signalement-modal',
            'Valider le signalement',
        ];
        yield 'SA - En cours' => [
            'admin-01@signal-logement.fr',
            '00000000-0000-0000-2022-000000000001',
            '#test-bouton-cloturer',
            'Clôturer',
        ];
        yield 'SA - Fermé' => [
            'admin-01@signal-logement.fr',
            '00000000-0000-0000-2023-000000000001',
            'button.fr-nav__link.fr-fi-lock-fill',
            'Rouvrir pour tous',
        ];

        yield '13 - RT - Nouveau' => [
            'admin-territoire-13-01@signal-logement.fr',
            '00000000-0000-0000-2023-000000000017',
            '#open-accept-signalement-modal',
            'Valider le signalement',
        ];
        yield '13 - RT - En cours' => [
            'admin-territoire-13-01@signal-logement.fr',
            '00000000-0000-0000-2022-000000000001',
            '#open-accept-affectation-modal',
            'Accepter',
        ];

        yield '01 - RT - Fermé' => [
            'admin-territoire-01-01@signal-logement.fr',
            '00000000-0000-0000-2022-000000000002',
            'button.fr-nav__link.fr-fi-lock-fill',
            'Rouvrir pour tous',
        ];

        yield '38 - RT - Affectation refusée' => [
            'admin-territoire-38-01@signal-logement.fr',
            '00000000-0000-0000-2023-000000000022',
            'button.fr-nav__link.fr-icon-checkbox-circle-fill',
            'Annuler le refus',
        ];
        yield '38 - RT - Affectation clôturée' => [
            'admin-territoire-38-01@signal-logement.fr',
            '00000000-0000-0000-2023-000000000023',
            'button.fr-nav__link.fr-fi-lock-fill',
            'Rouvrir pour DDT 38',
        ];

        yield '13 - Agent - Nouveau' => [
            'admin-partenaire-13-01@signal-logement.fr',
            '00000000-0000-0000-2022-000000000001',
            '.fr-icon-checkbox-circle-fill.fr-btn--success',
            'Accepter',
        ];
        yield '13 - Agent - En cours' => [
            'user-13-01@signal-logement.fr',
            '00000000-0000-0000-2023-000000000006',
            '#link-bouton-cloturer',
            'Clôturer',
        ];
        yield '13 - Agent - En cours - abonné alone' => [
            'user-13-01@signal-logement.fr',
            '00000000-0000-0000-2023-000000000026',
            'ul.fr-menu__list li button.fr-nav__link.fr-btn--icon-left.fr-icon-eye-off-line.disabled',
            'Se retirer du dossier',
        ];
        yield '30 - Agent - En cours - abonné' => [
            'user-partenaire-multi-ter-34-30@signal-logement.fr',
            '00000000-0000-0000-2025-000000000004',
            'ul.fr-menu__list li a.fr-nav__link.fr-btn--icon-left.fr-icon-eye-off-line',
            'Se retirer du dossier',
        ];
    }

    /**
     * @dataProvider provideSignalementSyncRoutes
     */
    public function testSignalementSyncedWithWarningEsabora(
        string $email,
        string $uuid,
        AffectationStatus $currentAffectationStatus,
        string $elementText,
    ): void {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        $user = $userRepository->findOneBy(['email' => $email]);
        $route = $generatorUrl->generate('back_signalement_view', ['uuid' => $uuid]);

        $signalement = static::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $uuid]);
        $affectation = $signalement->getAffectationForPartner($user->getPartnerInTerritory($signalement->getTerritory()));

        $client->loginUser($user);
        $client->request('GET', $route);
        $this->assertSelectorTextContains(
            '.fr-notice.fr-notice--warning',
            $elementText
        );

        $this->assertEquals($currentAffectationStatus, $affectation->getStatut());
    }

    public function provideSignalementSyncRoutes(): \Generator
    {
        yield 'Agent - ARS' => [
            'user-13-06@signal-logement.fr',
            '00000000-0000-0000-2024-000000000010',
            AffectationStatus::ACCEPTED,
            'La clôture de ce signalement est à réaliser sur SI-Santé Habitat (SI-SH).',
        ];

        yield 'Agent - SCHS' => [
            'user-13-05@signal-logement.fr',
            '00000000-0000-0000-2023-000000000012',
            AffectationStatus::WAIT,
            'Pour traiter ce signalement, veuillez d’abord accepter ou refuser le dossier dans Esabora.',
        ];
        yield 'Agent - EPCI' => [
            'user-13-15@signal-logement.fr',
            '00000000-0000-0000-2024-000000000010',
            AffectationStatus::ACCEPTED,
            'La clôture de ce signalement est à réaliser sur Esabora.',
        ];
    }

    public function testSignalementNDESuccessfullyDisplay(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);

        $client->loginUser($user);
        $client->request('GET', $route);
        $this->assertResponseIsSuccessful((string) $signalement->getId());
        $this->assertSelectorTextContains(
            '#title-nde',
            'Non décence énergétique'
        );
    }

    public function testAdminSubmitClotureSignalementWithEmailSentToPartners(): void
    {
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy([
            'reference' => '2022-8',
            'statut' => SignalementStatus::ACTIVE->value,
        ]);

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);

        $client->request('GET', $route);
        $client->submitForm(
            'Clôturer pour tous les partenaires',
            [
                'cloture[motifCloture]' => 'INSALUBRITE',
                'cloture[description]' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
                'cloture[isPublic]' => '0',
                'cloture[type]' => 'all',
            ]
        );

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertArrayHasKey('url', $response);
        $this->assertTrue($response['redirect']);
        $this->assertStringContainsString('/bo/signalements/', $response['url']);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2022-8']);
        $this->assertEquals(SignalementStatus::CLOSED, $signalement->getStatut());

        $client->enableProfiler();
        $this->assertEmailCount(1);
    }

    public function testAdminTerritorySubmitClotureSignalementWithEmailSentToPartnersAndUsagers(): void
    {
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy([
            'reference' => '2022-1',
            'statut' => SignalementStatus::ACTIVE->value,
        ]);

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);

        $client->request('GET', $route);
        $client->submitForm(
            'Clôturer pour tous les partenaires',
            [
                'cloture[motifCloture]' => 'INSALUBRITE',
                'cloture[description]' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
                'cloture[isPublic]' => '1',
                'cloture[type]' => 'all',
            ]
        );

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertArrayHasKey('url', $response);
        $this->assertTrue($response['redirect']);
        $this->assertStringContainsString('/bo/signalements/', $response['url']);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2022-1']);
        $this->assertEquals(SignalementStatus::CLOSED, $signalement->getStatut());

        $client->enableProfiler();
        $this->assertEmailCount(0);
    }

    public function testAdminPartnerSubmitClotureSignalementWithEmailSentToRT(): void
    {
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy([
            'reference' => '2023-26',
            'statut' => SignalementStatus::ACTIVE->value,
        ]);

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-partenaire-13-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);

        $client->request('GET', $route);
        $client->submitForm(
            'Clôturer pour Partenaire 13-01',
            [
                'cloture[motifCloture]' => 'INSALUBRITE',
                'cloture[description]' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
                'cloture[type]' => 'partner',
            ]
        );

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertArrayHasKey('url', $response);
        $this->assertTrue($response['redirect']);
        $this->assertStringContainsString('/bo/signalements/', $response['url']);

        $client->enableProfiler();
        $this->assertEmailCount(2);
    }

    public function testUserPartnerSubmitClotureSignalementWithEmailSentToPartnersAndRT(): void
    {
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy([
            'reference' => '2022-10',
            'statut' => SignalementStatus::ACTIVE->value,
        ]);

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);

        $client->request('GET', $route);
        $client->submitForm(
            'Clôturer pour Partenaire 13-02',
            [
                'cloture[motifCloture]' => 'RSD',
                'cloture[description]' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
                'cloture[type]' => 'partner',
            ]
        );

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $response);
        $this->assertArrayHasKey('url', $response);
        $this->assertTrue($response['redirect']);
        $this->assertStringContainsString('/bo/signalements/', $response['url']);

        $client->enableProfiler();
        $this->assertEmailCount(2);

        $this->assertEmailSubjectContains($this->getMailerMessages()[0], 'Nouveau suivi');
        $this->assertEmailSubjectContains($this->getMailerMessages()[1], 'a terminé son intervention');
    }

    public function testUserPartnerSubmitClotureSignalementWithoutMotifSuivi(): void
    {
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy([
            'reference' => '2022-10',
            'statut' => SignalementStatus::ACTIVE->value,
        ]);

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);

        $client->request('GET', $route);
        $client->submitForm(
            'Clôturer pour Partenaire 13-02',
            [
                'cloture[motifCloture]' => 'RSD',
                'cloture[type]' => 'partner',
                'cloture[description]' => 'bla',
            ]
        );
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertStringContainsString('Le contenu doit contenir au moins 10 caract\u00e8res.', (string) $client->getResponse()->getContent());
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testNewDeleteSignalement(): void
    {
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2022-13']);

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);
        $uuid = $signalement->getUuid();
        $csrfToken = $this->generateCsrfToken(
            $client,
            'signalement_delete_'.$signalement->getId()
        );

        $client->request(
            'POST',
            '/bo/signalements/'.$uuid.'/supprimer',
            [],
            [],
            ['Content-Type' => 'application/json'],
            (string) json_encode(['_token' => $csrfToken])
        );

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $response);
        $this->assertStringContainsString('a bien été supprimé.', $response['message']);
    }

    public function testSaveNewTagSignalement(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var TagRepository $tagRepository */
        $tagRepository = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepository->findOneBy(['label' => 'Péril', 'territory' => 13]);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-12']);

        $tagIds = array_map(
            function (Tag $tag) {
                return $tag->getId();
            },
            $signalement->getTags()->toArray());

        $tagIds[] = $tag->getId();
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_signalement_save_tags', ['uuid' => $signalement->getUuid()]);

        $client->request(
            'POST',
            $route,
            [
                'tag-ids' => implode(',', $tagIds),
                '_token' => $this->generateCsrfToken($client, 'signalement_save_tags'),
            ]
        );

        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains(
            '.fr-alert--success',
            'Les étiquettes ont bien été enregistrées.'
        );

        $signalement = $signalementRepository->findOneBy(['reference' => '2023-12']);
        $this->assertCount(2, $signalement->getTags());
    }
}
