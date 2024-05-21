<?php

namespace App\Tests\Unit\Service\Idoss;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Messenger\Message\Idoss\DossierMessage;
use App\Service\Idoss\IdossService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class IdossServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testPushDossierWithoutToken(): void
    {
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['email' => 'partenaire-13-05@histologe.fr']);
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);
        $affectation = $affectationRepository->findOneBy(['partner' => $partner]);

        $dossierMessage = new DossierMessage($affectation);

        $futureDate = (new \DateTime())->modify('+1 day');
        $tokenResponse = '{"token": "token.demo.test","expirationDate": "'.$futureDate->format('c').'"}';
        $tokenMockResponse = new MockResponse($tokenResponse);
        $idossResponse = '{"message":"Dossier créer avec succès !"}';
        $idossMockResponse = new MockResponse($idossResponse);

        $mockHttpClient = new MockHttpClient([$tokenMockResponse, $idossMockResponse]);
        $containerBagInterface = $this->createMock(ContainerBagInterface::class);

        $idossService = new IdossService($mockHttpClient, $containerBagInterface, $this->entityManager);
        $response = $idossService->pushDossier($dossierMessage);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($idossResponse, $response->getContent());
        $this->assertEquals($partner->getIdossToken(), 'token.demo.test');
    }

    public function testPushDossierWithValidToken(): void
    {
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['email' => 'partenaire-13-05@histologe.fr']);
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);
        $affectation = $affectationRepository->findOneBy(['partner' => $partner]);
        $affectation->getPartner()->setIdossToken('TEST');
        $affectation->getPartner()->setIdossTokenExpirationDate((new \DateTimeImmutable())->modify('+1 day'));

        $dossierMessage = new DossierMessage($affectation);

        $idossResponse = '{"message":"Dossier créer avec succès !"}';
        $idossMockResponse = new MockResponse($idossResponse);

        $mockHttpClient = new MockHttpClient($idossMockResponse);
        $containerBagInterface = $this->createMock(ContainerBagInterface::class);

        $idossService = new IdossService($mockHttpClient, $containerBagInterface, $this->entityManager);
        $response = $idossService->pushDossier($dossierMessage);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($idossResponse, $response->getContent());
        $this->assertEquals($partner->getIdossToken(), 'TEST');
    }

    public function testPushDossierWithExpiredToken(): void
    {
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['email' => 'partenaire-13-05@histologe.fr']);
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);
        $affectation = $affectationRepository->findOneBy(['partner' => $partner]);
        $affectation->getPartner()->setIdossToken('TEST');
        $affectation->getPartner()->setIdossTokenExpirationDate((new \DateTimeImmutable())->modify('-1 day'));

        $dossierMessage = new DossierMessage($affectation);

        $futureDate = (new \DateTime())->modify('-1 day');
        $tokenResponse = '{"token": "token.demo.test","expirationDate": "'.$futureDate->format('c').'"}';
        $tokenMockResponse = new MockResponse($tokenResponse);
        $idossResponse = '{"message":"Dossier créer avec succès !"}';
        $idossMockResponse = new MockResponse($idossResponse);

        $mockHttpClient = new MockHttpClient([$tokenMockResponse, $idossMockResponse]);
        $containerBagInterface = $this->createMock(ContainerBagInterface::class);

        $idossService = new IdossService($mockHttpClient, $containerBagInterface, $this->entityManager);
        $response = $idossService->pushDossier($dossierMessage);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($idossResponse, $response->getContent());
        $this->assertEquals($partner->getIdossToken(), 'token.demo.test');
    }
}
