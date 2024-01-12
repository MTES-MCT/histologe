<?php

namespace App\Tests\Functional\Factory\Oilhi;

use App\Entity\Signalement;
use App\Factory\Oilhi\DossierMessageFactory;
use App\Repository\SignalementRepository;
use App\Tests\FixturesHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class DossierMessageFactoryTest extends KernelTestCase
{
    use FixturesHelper;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    public function testDossierMessageFullyCreated()
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2024-01']);

        $affectation = $signalement->getAffectations()->first();

        $urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager
            ->expects($this->once())
            ->method('getToken')
            ->willReturn(new CsrfToken('suivi_signalement_ext_file_view', 'random_value'));

        $dossierMessageFactory = new DossierMessageFactory($urlGenerator, $csrfTokenManager, true);

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

        $this->assertCount(2, explode(',', $dossierMessage->getCourrielContributeurs()));
    }
}
