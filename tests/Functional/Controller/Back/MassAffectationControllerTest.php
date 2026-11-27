<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Messenger\Message\MassAffectationMessage;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\MessageHelper;
use App\Tests\SessionHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MassAffectationControllerTest extends WebTestCase
{
    use SessionHelper;

    private const string SUPER_ADMIN_EMAIL = 'admin-01@signal-logement.fr';
    private const string PARTNER_NAME = 'Partenaire 13-03';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
    }

    public function testAnalyzeClassifiesSignalements(): void
    {
        $this->loginAs(self::SUPER_ADMIN_EMAIL);
        $partner = $this->getPartner(self::PARTNER_NAME);

        $this->requestAnalyze([
            'territoire' => $this->getTerritory('13')->getId(),
            'status' => 'en_cours',
            'partnerId' => $partner->getId(),
        ]);

        $this->assertResponseIsSuccessful();
        $response = $this->getJsonResponse();

        $toAffect = array_column($response['signalementsToAffect'], 'reference');
        $alreadyAffected = array_column($response['signalementsAlreadyAffected'], 'reference');
        $this->assertContains('2022-4', $toAffect);
        $this->assertNotContains('2022-8', $toAffect);
        $this->assertContains('2022-8', $alreadyAffected);
        $this->assertFalse($response['partnerInterconnection']);

        $firstSignalement = $response['signalementsToAffect'][0];
        $this->assertArrayHasKey('id', $firstSignalement);
        $this->assertArrayHasKey('identite_occupant', $firstSignalement);
        $this->assertArrayHasKey('adresse', $firstSignalement);
    }

    public function testAnalyzePartnerNotFound(): void
    {
        $this->loginAs(self::SUPER_ADMIN_EMAIL);

        $this->requestAnalyze([
            'territoire' => $this->getTerritory('13')->getId(),
            'status' => 'en_cours',
            'partnerId' => 999999,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertSame('Partenaire introuvable', $this->getJsonResponse()['message']);
    }

    public function testAnalyzePartnerOtherTerritory(): void
    {
        $this->loginAs(self::SUPER_ADMIN_EMAIL);

        $this->requestAnalyze([
            'territoire' => $this->getTerritory('34')->getId(),
            'status' => 'en_cours',
            'partnerId' => $this->getPartner(self::PARTNER_NAME)->getId(),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testAnalyzeForbiddenForNonSuperAdmin(): void
    {
        $this->loginAs('admin-territoire-13-01@signal-logement.fr');

        $this->requestAnalyze([
            'territoire' => $this->getTerritory('13')->getId(),
            'status' => 'en_cours',
            'partnerId' => $this->getPartner(self::PARTNER_NAME)->getId(),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testValidateInvalidCsrf(): void
    {
        $this->loginAs(self::SUPER_ADMIN_EMAIL);

        $this->requestValidate([
            '_token' => 'bad_token',
            'partnerId' => $this->getPartner(self::PARTNER_NAME)->getId(),
            'ids' => [$this->getSignalement('2022-4')->getId()],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame(MessageHelper::ERROR_MESSAGE_CSRF, $this->getJsonResponse()['message']);
        $this->assertNoMessageDispatched();
    }

    public function testValidateEmptyIds(): void
    {
        $this->loginAs(self::SUPER_ADMIN_EMAIL);

        $this->requestValidate([
            '_token' => $this->generateCsrfToken($this->client, 'mass_affectation_validate'),
            'partnerId' => $this->getPartner(self::PARTNER_NAME)->getId(),
            'ids' => [],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame('Aucun signalement à affecter.', $this->getJsonResponse()['message']);
        $this->assertNoMessageDispatched();
    }

    public function testValidatePartnerNotFound(): void
    {
        $this->loginAs(self::SUPER_ADMIN_EMAIL);

        $this->requestValidate([
            '_token' => $this->generateCsrfToken($this->client, 'mass_affectation_validate'),
            'partnerId' => 999999,
            'ids' => [$this->getSignalement('2022-4')->getId()],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertNoMessageDispatched();
    }

    public function testValidateArchivedPartner(): void
    {
        $this->loginAs(self::SUPER_ADMIN_EMAIL);

        $this->requestValidate([
            '_token' => $this->generateCsrfToken($this->client, 'mass_affectation_validate'),
            'partnerId' => $this->getPartner('Partenaire 13-07')->getId(),
            'ids' => [$this->getSignalement('2022-4')->getId()],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertSame('Partenaire archivé', $this->getJsonResponse()['message']);
        $this->assertNoMessageDispatched();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIneligibleSignalement(): iterable
    {
        yield 'signalement fermé' => ['2022-3'];
        yield 'signalement d\'un autre territoire' => ['2024-08'];
    }

    #[DataProvider('provideIneligibleSignalement')]
    public function testValidateIneligibleSignalements(string $ineligibleReference): void
    {
        $this->loginAs(self::SUPER_ADMIN_EMAIL);

        $this->requestValidate([
            '_token' => $this->generateCsrfToken($this->client, 'mass_affectation_validate'),
            'partnerId' => $this->getPartner(self::PARTNER_NAME)->getId(),
            'ids' => [
                $this->getSignalement('2022-4')->getId(),
                $this->getSignalement($ineligibleReference)->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertStringContainsString('plus éligibles', $this->getJsonResponse()['message']);
        $this->assertNoMessageDispatched();
    }

    public function testValidateDispatchesMessage(): void
    {
        $user = $this->loginAs(self::SUPER_ADMIN_EMAIL);
        $partner = $this->getPartner(self::PARTNER_NAME);
        $signalementId = $this->getSignalement('2022-4')->getId();
        $otherSignalementId = $this->getSignalement('2022-5')->getId();

        $this->requestValidate([
            '_token' => $this->generateCsrfToken($this->client, 'mass_affectation_validate'),
            'partnerId' => $partner->getId(),
            'ids' => [$signalementId, $otherSignalementId, $signalementId],
        ]);

        $this->assertResponseIsSuccessful();

        $envelopes = $this->getTransport()->getSent();
        $this->assertCount(1, $envelopes);
        $message = $envelopes[0]->getMessage();
        $this->assertInstanceOf(MassAffectationMessage::class, $message);
        $this->assertSame($partner->getId(), $message->getPartnerId());
        $this->assertSame($user->getId(), $message->getUserId());
        $this->assertSame([$signalementId, $otherSignalementId], $message->getSignalementIds());
    }

    private function loginAs(string $email): User
    {
        /** @var User $user */
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        $this->client->loginUser($user);

        return $user;
    }

    private function getPartner(string $name): Partner
    {
        /** @var Partner $partner */
        $partner = static::getContainer()->get(PartnerRepository::class)->findOneBy(['nom' => $name]);

        return $partner;
    }

    private function getTerritory(string $zip): Territory
    {
        /** @var Territory $territory */
        $territory = static::getContainer()->get(TerritoryRepository::class)->findOneBy(['zip' => $zip]);

        return $territory;
    }

    private function getSignalement(string $reference): Signalement
    {
        /** @var Signalement $signalement */
        $signalement = static::getContainer()->get(SignalementRepository::class)->findOneBy(['reference' => $reference]);

        return $signalement;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function requestAnalyze(array $query): void
    {
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_signalements_mass_affectation_analyze', $query);
        $this->client->request('GET', $url);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requestValidate(array $payload): void
    {
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_signalements_mass_affectation_validate');
        $this->client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode($payload)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getJsonResponse(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true);
    }

    private function getTransport(): InMemoryTransport
    {
        /** @var InMemoryTransport $transport */
        $transport = static::getContainer()->get('messenger.transport.async_priority_high');

        return $transport;
    }

    private function assertNoMessageDispatched(): void
    {
        $this->assertCount(0, $this->getTransport()->getSent());
    }
}
