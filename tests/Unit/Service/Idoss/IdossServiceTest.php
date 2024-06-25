<?php

namespace App\Tests\Unit\Service\Idoss;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Manager\JobEventManager;
use App\Messenger\Message\Idoss\DossierMessage;
use App\Service\Idoss\IdossService;
use App\Service\ImageManipulationHandler;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Serializer\SerializerInterface;

class IdossServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    protected function getIdossService(MockHttpClient $mockHttpClient): IdossService
    {
        $containerBagInterface = $this->createMock(ContainerBagInterface::class);
        $jobEventManager = $this->createMock(JobEventManager::class);
        $serializerMock = $this->createMock(SerializerInterface::class);
        $fileStorageMock = $this->createMock(FilesystemOperator::class);
        $imageManipulationHandlerMock = $this->createMock(ImageManipulationHandler::class);

        return new IdossService($mockHttpClient, $containerBagInterface, $this->entityManager, $jobEventManager, $serializerMock, $fileStorageMock, $imageManipulationHandlerMock);
    }

    public function testWithoutToken(): void
    {
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['email' => 'partenaire-13-05@histologe.fr']);
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);
        $affectation = $affectationRepository->findOneBy(['partner' => $partner]);

        $dossierMessage = new DossierMessage($affectation);

        $futureDate = (new \DateTime())->modify('+1 day');
        $tokenResponse = '{"token": "token.demo.test","expirationDate": "'.$futureDate->format('c').'"}';
        $tokenMockResponse = new MockResponse($tokenResponse);
        $idossResponse = '{"message":"Dossier créer avec succès !","uuid":"6672a85be1a54","id":"72303"}';
        $idossMockResponse = new MockResponse($idossResponse);

        $mockHttpClient = new MockHttpClient([$tokenMockResponse, $idossMockResponse]);
        $idossService = $this->getIdossService($mockHttpClient);

        $idossService->pushDossier($dossierMessage);

        $this->assertEquals($partner->getIdossToken(), 'token.demo.test');
    }

    public function testWithValidToken(): void
    {
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['email' => 'partenaire-13-05@histologe.fr']);
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);
        $affectation = $affectationRepository->findOneBy(['partner' => $partner]);
        $affectation->getPartner()->setIdossToken('TEST');
        $affectation->getPartner()->setIdossTokenExpirationDate((new \DateTimeImmutable())->modify('+1 day'));

        $dossierMessage = new DossierMessage($affectation);

        $idossResponse = '{"message":"Dossier créer avec succès !","uuid":"6672a85be1a54","id":"72303"}';
        $idossMockResponse = new MockResponse($idossResponse);

        $mockHttpClient = new MockHttpClient($idossMockResponse);
        $idossService = $this->getIdossService($mockHttpClient);

        $idossService->pushDossier($dossierMessage);

        $this->assertEquals($partner->getIdossToken(), 'TEST');
    }

    public function testWithExpiredToken(): void
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
        $idossResponse = '{"message":"Dossier créer avec succès !","uuid":"6672a85be1a54","id":"72303"}';
        $idossMockResponse = new MockResponse($idossResponse);

        $mockHttpClient = new MockHttpClient([$tokenMockResponse, $idossMockResponse]);
        $idossService = $this->getIdossService($mockHttpClient);

        $idossService->pushDossier($dossierMessage);

        $this->assertEquals($partner->getIdossToken(), 'token.demo.test');
    }
}
