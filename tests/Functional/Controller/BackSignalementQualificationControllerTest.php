<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class BackSignalementQualificationControllerTest extends WebTestCase
{
    /**
     * @dataProvider provideDataNDE
     *
     * @param array<mixed> $payload
     */
    public function testSubmitQualificationNDE(
        array $payload,
        int $superficie,
        int $consommationEnergie,
        QualificationStatus $qualificationStatus,
    ): void {
        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];
        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertEquals(Qualification::NON_DECENCE_ENERGETIQUE, $signalementQualification->getQualification());

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
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
        $payload['_token'] = $crawler->filter('#signalement-edit-nde-form input[name=_token]')->attr('value');
        $client->request('GET', $route);
        $this->assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $client->getResponse()->getStatusCode(),
            \sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );

        $client->request(
            'POST',
            $route,
            [],
            [],
            [],
            (string) json_encode($payload)
        );

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];

        $this->assertEquals($superficie, $signalement->getSuperficie());
        $this->assertEquals($consommationEnergie, $signalementQualification->getDetails()['consommation_energie']);
        $this->assertEquals($qualificationStatus, $signalementQualification->getStatus());

        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('closeModal', $response);
        $this->assertTrue($response['stayOnPage']);
        $msgFlash = 'Les modifications ont bien été enregistrées.';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
    }

    public function provideDataNDE(): \Generator
    {
        yield 'Submit qualification NDE Check' => [
            [
                'superficie' => 234,
                'dpe' => null,
                'consommationEnergie' => 545,
            ],
            234,
            545,
            QualificationStatus::NDE_CHECK,
        ];

        yield 'Submit qualification NDE OK' => [
            [
                'dpe' => true,
                'superficie' => 234,
                'dateDernierDPE' => '2023-01-08',
                'consommationEnergie' => 120,
            ],
            234,
            120,
            QualificationStatus::NDE_OK,
        ];

        yield 'Submit qualification NDE Averee' => [
            [
                'dpe' => true,
                'superficie' => 234,
                'dateDernierDPE' => '2023-01-02',
                'dateDernierBail' => '2023-01-02',
                'consommationEnergie' => 545,
            ],
            234,
            545,
            QualificationStatus::NDE_AVEREE,
        ];
    }
}
