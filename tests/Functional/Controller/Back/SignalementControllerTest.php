<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
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

        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);

        $client->loginUser($user);
        $client->request('GET', $route);
        if (Signalement::STATUS_ARCHIVED !== $signalement->getStatut()) {
            $this->assertResponseIsSuccessful($signalement->getId());
            $this->assertSelectorTextContains(
                'h1.fr-h2.fr-text-label--blue-france',
                '#'.$signalement->getReference().' -',
                $signalement->getReference()
            );
        } else {
            $this->assertResponseRedirects('/bo/signalements/');
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

    public function testSignalementNDESuccessfullyDisplay(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
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
        $this->assertResponseIsSuccessful($signalement->getId());
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
            'statut' => Signalement::STATUS_ACTIVE,
        ]);

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);

        $client->request('GET', $route);
        $client->submitForm(
            'Cloturer pour tous les partenaires',
            [
                'cloture[motif]' => 'INSALUBRITE',
                'cloture[suivi]' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
                'cloture[publicSuivi]' => '0',
                'cloture[type]' => 'all',
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/');
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2022-8']);
        $this->assertEquals(Signalement::STATUS_CLOSED, $signalement->getStatut());

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
            'statut' => Signalement::STATUS_ACTIVE,
        ]);

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);

        $client->request('GET', $route);
        $client->submitForm(
            'Cloturer pour tous les partenaires',
            [
                'cloture[motif]' => 'INSALUBRITE',
                'cloture[suivi]' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
                'cloture[publicSuivi]' => '1',
                'cloture[type]' => 'all',
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/');
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2022-1']);
        $this->assertEquals(Signalement::STATUS_CLOSED, $signalement->getStatut());

        $client->enableProfiler();
        $this->assertEmailCount(3);
    }

    public function testAdminPartnerSubmitClotureSignalementWithEmailSentToPartners(): void
    {
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy([
            'reference' => '2023-26',
            'statut' => Signalement::STATUS_ACTIVE,
        ]);

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-partenaire-13-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);

        $client->request('GET', $route);
        $client->submitForm(
            'Cloturer pour Partenaire 13-01',
            [
                'cloture[motif]' => 'INSALUBRITE',
                'cloture[suivi]' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
                'cloture[type]' => 'partner',
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/');

        $client->enableProfiler();
        $this->assertEmailCount(1);
    }

    public function testUserPartnerSubmitClotureSignalementWithEmailSentToPartners(): void
    {
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy([
            'reference' => '2022-10',
            'statut' => Signalement::STATUS_ACTIVE,
        ]);

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-13-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);

        $client->request('GET', $route);
        $client->submitForm(
            'Cloturer pour Partenaire 13-02',
            [
                'cloture[motif]' => 'RSD',
                'cloture[suivi]' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
                'cloture[type]' => 'partner',
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/');

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
            'statut' => Signalement::STATUS_ACTIVE,
        ]);

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-13-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_signalement_view', ['uuid' => $signalement->getUuid()]);

        $client->request('GET', $route);
        $client->submitForm(
            'Cloturer pour Partenaire 13-02',
            [
                'cloture[motif]' => 'RSD',
                'cloture[type]' => 'partner',
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $client->followRedirect();
        $this->assertSelectorTextContains('.fr-alert--error p', 'Le motif de suivi doit contenir au moins 10 caractères.');
    }

    public function testNewDeleteSignalement(): void
    {
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2022-10']);

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);
        $uuid = $signalement->getUuid();
        $csrfToken = $this->generateCsrfToken(
            $client,
            'signalement_delete_'.$signalement->getId()
        );

        $client->request(
            'POST',
            '/bo/signalements/v2/'.$uuid.'/supprimer',
            [],
            [],
            ['Content-Type' => 'application/json'],
            json_encode(['_token' => $csrfToken])
        );

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $response);
        $this->assertStringContainsString('a bien été supprimé.', $response['message']);
    }
}
