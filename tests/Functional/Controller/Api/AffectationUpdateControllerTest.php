<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\NotificationType;
use App\Repository\NotificationRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Tests\ApiHelper;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AffectationUpdateControllerTest extends WebTestCase
{
    use ApiHelper;
    private KernelBrowser $client;
    private SignalementRepository $signalementRepository;
    private UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository;
    private NotificationRepository $notificationRepository;

    private const string TRANSITION_ERROR_MESSAGE = 'Cette transition n\'est pas valide';
    private const string AFFECTATION_NOT_FOUND_ERROR_MESSAGE = 'Affectation introuvable';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy([
            'email' => 'api-01@signal-logement.fr',
        ]);
        $this->signalementRepository = self::getContainer()->get(SignalementRepository::class);
        $this->userSignalementSubscriptionRepository = self::getContainer()->get(UserSignalementSubscriptionRepository::class);
        $this->notificationRepository = self::getContainer()->get(NotificationRepository::class);

        $this->client->loginUser($user, 'api');
    }

    /**
     * @dataProvider provideValidTransitionData
     *
     * @param array<mixed> $payload
     */
    public function testValidWorkflow(string $signalementUuid, array $payload, string $statut, int $mailSent): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => $signalementUuid]);
        /** @var Collection<int, Affectation> $affectations */
        $affectations = $signalement->getAffectations();

        /** @var Affectation $affectation */
        $affectation = $affectations->filter(function (Affectation $affectation) {
            return 'Partenaire 13-01' === $affectation->getPartner()->getNom();
        })->current();

        $this->patchAffectation($affectation->getUuid(), $payload);
        $this->assertResponseIsSuccessful();
        $this->assertEquals($statut, $affectation->getStatut()->value);
        $this->assertEmailCount($mailSent);
        if (AffectationStatus::ACCEPTED->value === $statut) {
            $subscriptions = $this->userSignalementSubscriptionRepository->findBy(['signalement' => $signalement]);
            $this->assertCount(6, $subscriptions); // 6 utilisateurs du partenaire moins 1 User API + 1 RT déja abonné
            $notification = $this->notificationRepository->findBy(['signalement' => $signalement, 'type' => NotificationType::NOUVEL_ABONNEMENT]);
            $this->assertCount(5, $notification);
        }
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    /**
     * @dataProvider provideUnvalidData
     *
     * @param array<mixed> $payload
     */
    public function testInvalidWorkflow(string $signalementUuid, array $payload, string $errorMessage, int $httpCodeStatus): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => $signalementUuid]);
        /** @var Collection<int, Affectation> $affectations */
        $affectations = $signalement?->getAffectations();

        $affectation = null;
        /* @var Affectation $affectation */
        if (null !== $affectations) {
            $affectation = $affectations->filter(function (Affectation $affectation) {
                return 'Partenaire 13-01' === $affectation->getPartner()->getNom();
            })->current();
        }
        $this->patchAffectation($affectation?->getUuid(), $payload);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($httpCodeStatus, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString($errorMessage, $response['message']);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    /**
     * @param array<mixed> $payload
     */
    private function patchAffectation(?string $affectationUuid, array $payload): void
    {
        if (null === $affectationUuid) {
            $affectationUuid = 'wrong-uuid';
        }
        $this->client->request(
            method: 'PATCH',
            uri: '/api/affectations/'.$affectationUuid,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );
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
        yield 'NOUVEAU ==> REFUSE' => [
            '00000000-0000-0000-2022-000000000001',
            [
                'statut' => 'REFUSE',
                'motifRefus' => 'DOUBLON',
                'message' => 'lorem ipsum dolor sit amet',
            ],
            'REFUSE',
            1,
        ];
        yield 'EN_COURS ==> FERME' => [
            '00000000-0000-0000-2022-000000000006',
            [
                'statut' => 'FERME',
                'motifCloture' => 'REFUS_DE_VISITE',
                'message' => 'lorem ipsum dolor sit amet',
            ],
            'FERME',
            2,
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

    public function provideUnvalidData(): \Generator
    {
        yield 'NOUVEAU ==> FERME' => [
            '00000000-0000-0000-2022-000000000001',
            [
                'statut' => 'FERME',
                'motifCloture' => 'RELOGEMENT_OCCUPANT',
                'message' => 'Hello world buddy!!!!',
            ],
            self::TRANSITION_ERROR_MESSAGE,
            Response::HTTP_FORBIDDEN,
        ];
        yield 'EN_COURS ==> NOUVEAU' => [
            '00000000-0000-0000-2022-000000000006',
            [
                'statut' => 'NOUVEAU',
                'notifyUsager' => true,
            ],
            self::TRANSITION_ERROR_MESSAGE,
            Response::HTTP_FORBIDDEN,
        ];

        yield 'FERME ==> EN_COURS' => [
            '00000000-0000-0000-2023-000000000013',
            [
                'statut' => 'EN_COURS',
            ],
            self::TRANSITION_ERROR_MESSAGE,
            Response::HTTP_FORBIDDEN,
        ];

        yield 'NOUVEAU ==> EN_COURS' => [
            'wrong-uuid',
            [
                'statut' => 'EN_COURS',
            ],
            self::AFFECTATION_NOT_FOUND_ERROR_MESSAGE,
            Response::HTTP_NOT_FOUND,
        ];
    }
}
