<?php

namespace App\Tests\Functional\Factory\Oilhi;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Factory\Interconnection\Oilhi\DossierMessageFactory;
use App\Messenger\Message\Oilhi\DossierMessage;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Service\Interconnection\Oilhi\HookZapierService;
use App\Tests\FixturesHelper;
use CoopTilleuls\UrlSignerBundle\UrlSigner\UrlSignerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DossierMessageFactoryTest extends KernelTestCase
{
    use FixturesHelper;

    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private SerializerInterface $serializer;
    private UrlSignerInterface $urlSigner;

    private const string PATTERN_EXPECTED_DATE_FORMAT = '/^\d{4}-\d{2}-\d{2}$/';

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->logger = self::getContainer()->get('logger');
        $this->serializer = self::getContainer()->get('serializer');
        $this->urlSigner = self::getContainer()->get(UrlSignerInterface::class);
    }

    /** @dataProvider provideReference */
    public function testDossierMessageFullyCreated(string $reference): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => $reference]);
        if ('2024-01' === $reference) {
            $affectation = $signalement->getAffectations()->first();
        } else {
            /** @var PartnerRepository $partnerRepository */
            $partnerRepository = $this->entityManager->getRepository(Partner::class);
            $partner = $partnerRepository->findOneBy(['nom' => 'Partenaire 62-01']);
            $affectation = (new Affectation())
                ->setPartner($partner)
                ->setSignalement($signalement)
                ->setTerritory($signalement->getTerritory());
        }

        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);

        $dossierMessageFactory = new DossierMessageFactory($urlGenerator, true, $this->urlSigner);

        $this->assertFalse($dossierMessageFactory->supports($affectation));

        $dossierMessage = $dossierMessageFactory->createInstance($affectation);

        $this->assertNotEmpty($dossierMessage->getUuidSignalement());
        $this->assertNotEmpty($dossierMessage->getDateDepotSignalement());
        $this->assertNotEmpty($dossierMessage->getDateAffectationSignalement());
        $this->assertNotEmpty($dossierMessage->getCourrielContributeurs());
        $this->assertNotEmpty($dossierMessage->getAdresseSignalement());
        $this->assertNotEmpty($dossierMessage->getCommuneSignalement());
        $this->assertNotEmpty($dossierMessage->getCodePostalSignalement());
        $this->assertNotEmpty($dossierMessage->getTypeDeclarant());
        $this->assertNotEmpty($dossierMessage->getTelephoneDeclarant());
        $this->assertNotEmpty($dossierMessage->getCourrielDeclarant());
        $this->assertNotEmpty($dossierMessage->getNbOccupants());

        $this->assertEquals('⌛️ Procédure en cours', $dossierMessage->getStatut());

        $this->assertMatchesRegularExpression(
            self::PATTERN_EXPECTED_DATE_FORMAT,
            $dossierMessage->getDateDepotSignalement());
        $this->assertMatchesRegularExpression(
            self::PATTERN_EXPECTED_DATE_FORMAT,
            $dossierMessage->getDateAffectationSignalement());
        $this->assertMatchesRegularExpression(
            self::PATTERN_EXPECTED_DATE_FORMAT,
            $dossierMessage->getDateVisite()
        );

        $this->assertCount(2, explode(',', $dossierMessage->getCourrielContributeurs()));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ExceptionInterface
     */
    public function testPushDossierWithException(): void
    {
        $faker = Factory::create();

        // Throw an exception when the HTTP client is used
        /** @var MockObject&HttpClientInterface $mockHttpClient */
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->method('request')
            ->willThrowException(new TransportException('HTTP request failed'));

        $hookZapierService = new HookZapierService(
            $mockHttpClient,
            $this->logger,
            $this->serializer, // @phpstan-ignore-line
            'ZAPIER_OILHI_TOKEN',
            'USER_ID',
            'ZAP_ID',
        );

        $dossierMessage = (new DossierMessage())
            ->setPartnerId(1)
            ->setSignalementId(1)
            ->setAction('push_dossier')
            ->setSignalementUrl($faker->url());

        $response = $hookZapierService->pushDossier($dossierMessage);

        $responseData = json_decode((string) $response->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('HTTP request failed', $responseData['message']);
    }

    public function provideReference(): \Generator
    {
        yield 'Dossier avec l\'ancien formulaire 2024-01' => ['2024-01'];
        yield 'Dossier avec le nouveau formulaire 2024-02' => ['2024-02'];
    }
}
