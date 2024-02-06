<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class BackSignalementQualificationControllerTest extends WebTestCase
{
    public function testSubmitQualificationNDECheckSignalement()
    {
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
        $this->assertEquals(Qualification::NON_DECENCE_ENERGETIQUE, $signalementQualification->getQualification());

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate(
            'back_signalement_qualification_editer',
            ['uuid' => $signalement->getUuid(), 'signalementQualification' => $signalementQualification->getId()]
        );

        $routeSignalementView = $router->generate(
            'back_signalement_view',
            ['uuid' => $signalement->getUuid()]
        );

        $crawler = $client->request('GET', $routeSignalementView);
        $token = $crawler->filter('#signalement-edit-nde-form input[name=_token]')->attr('value');
        $client->request('GET', $route);
        $this->assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );

        $client->request(
            'POST',
            $route,
            [],
            [],
            [],
            json_encode([
                'superficie' => 234,
                'dpe' => null,
                'consommationEnergie' => 545,
                '_token' => $token,
            ])
        );

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];

        $this->assertEquals(234, $signalement->getSuperficie());
        $this->assertEquals(545, $signalementQualification->getDetails()['consommation_energie']);
        $this->assertEquals(QualificationStatus::NDE_CHECK, $signalementQualification->getStatus());

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
    }

    public function testSubmitQualificationNDEOKSignalement()
    {
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
        $this->assertEquals(Qualification::NON_DECENCE_ENERGETIQUE, $signalementQualification->getQualification());

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate(
            'back_signalement_qualification_editer',
            ['uuid' => $signalement->getUuid(), 'signalementQualification' => $signalementQualification->getId()]
        );

        $routeSignalementView = $router->generate(
            'back_signalement_view',
            ['uuid' => $signalement->getUuid()]
        );

        $crawler = $client->request('GET', $routeSignalementView);
        $token = $crawler->filter('#signalement-edit-nde-form input[name=_token]')->attr('value');
        $client->request('GET', $route);
        $this->assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );

        $client->request(
            'POST',
            $route,
            [],
            [],
            [],
            json_encode([
                'dpe' => true,
                'superficie' => 234,
                'dateDernierDPE' => '2023-01-08',
                'consommationEnergie' => 120,
                '_token' => $token,
            ])
        );

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];

        $this->assertEquals(234, $signalement->getSuperficie());
        $this->assertEquals(120, $signalementQualification->getDetails()['consommation_energie']);
        $this->assertEquals(QualificationStatus::NDE_OK, $signalementQualification->getStatus());

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
    }

    public function testSubmitQualificationNDEArchivedSignalement()
    {
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
        $this->assertEquals(Qualification::NON_DECENCE_ENERGETIQUE, $signalementQualification->getQualification());

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate(
            'back_signalement_qualification_editer',
            ['uuid' => $signalement->getUuid(), 'signalementQualification' => $signalementQualification->getId()]
        );

        $routeSignalementView = $router->generate(
            'back_signalement_view',
            ['uuid' => $signalement->getUuid()]
        );

        $crawler = $client->request('GET', $routeSignalementView);
        $token = $crawler->filter('#signalement-edit-nde-form input[name=_token]')->attr('value');
        $client->request('GET', $route);
        $this->assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );

        $client->request(
            'POST',
            $route,
            [],
            [],
            [],
            json_encode([
                'dpe' => true,
                'superficie' => 234,
                'dateDernierDPE' => '2023-01-02',
                'dateDernierBail' => '1970-01-01',
                'consommationEnergie' => 545,
                '_token' => $token,
            ])
        );

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];

        $this->assertEquals(234, $signalement->getSuperficie());
        $this->assertEquals(545, $signalementQualification->getDetails()['consommation_energie']);
        $this->assertEquals(QualificationStatus::ARCHIVED, $signalementQualification->getStatus());

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
    }

    public function testSubmitQualificationNDEAvereeSignalement()
    {
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
        $this->assertEquals(Qualification::NON_DECENCE_ENERGETIQUE, $signalementQualification->getQualification());

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate(
            'back_signalement_qualification_editer',
            ['uuid' => $signalement->getUuid(), 'signalementQualification' => $signalementQualification->getId()]
        );

        $routeSignalementView = $router->generate(
            'back_signalement_view',
            ['uuid' => $signalement->getUuid()]
        );

        $crawler = $client->request('GET', $routeSignalementView);
        $token = $crawler->filter('#signalement-edit-nde-form input[name=_token]')->attr('value');
        $client->request('GET', $route);
        $this->assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );

        $client->request(
            'POST',
            $route,
            [],
            [],
            [],
            json_encode([
                'dpe' => true,
                'superficie' => 234,
                'dateDernierDPE' => '2023-01-02',
                'dateDernierBail' => '2023-01-02',
                'consommationEnergie' => 545,
                '_token' => $token,
            ])
        );

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];

        $this->assertEquals(234, $signalement->getSuperficie());
        $this->assertEquals(545, $signalementQualification->getDetails()['consommation_energie']);
        $this->assertEquals(QualificationStatus::NDE_AVEREE, $signalementQualification->getStatus());

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
    }
}
