<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class SignalementControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    /**
     * @dataProvider provideRoutesEdit
     */
    public function testSignalementEditionSuccessfullyDisplay(string $route, Signalement $signalement): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);

        $client->loginUser($user);
        $client->request('GET', $route);

        if (Signalement::STATUS_ACTIVE === $signalement->getStatut()) {
            $this->assertResponseIsSuccessful($signalement->getId());
            $this->assertSelectorTextContains(
                'h1.fr-h2',
                'Edition signalement #'.$signalement->getReference(),
                $signalement->getReference()
            );
        } else {
            $this->assertResponseRedirects('/bo/signalements/');
        }
    }

    public function provideRoutesEdit(): \Generator
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);

        $signalements = $signalementRepository->findAll();

        /** @var Signalement $signalement */
        foreach ($signalements as $signalement) {
            $route = $generatorUrl->generate('back_signalement_edit', ['uuid' => $signalement->getUuid()]);
            yield $route => [$route, $signalement];
        }
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
}
