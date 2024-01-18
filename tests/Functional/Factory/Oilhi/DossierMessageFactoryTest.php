<?php

namespace App\Tests\Functional\Factory\Oilhi;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Factory\Interconnection\Oilhi\DossierMessageFactory;
use App\Repository\PartnerRepository;
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

    private const PATTERN_EXPECTED_DATE_FORMAT = '/^\d{4}-\d{2}-\d{2}$/';

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
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

        $urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager
            ->expects($this->once())
            ->method('getToken')
            ->willReturn(new CsrfToken('suivi_signalement_ext_file_view', 'random_value'));

        $dossierMessageFactory = new DossierMessageFactory($urlGenerator, $csrfTokenManager, true);

        $this->assertTrue($dossierMessageFactory->supports($affectation));

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

        $this->assertEquals('⌛️ Procédure en cours', $dossierMessage->getStatut());
        $this->assertNotEmpty($dossierMessage->getDesordresCategorie());
        $this->assertNotEmpty($dossierMessage->getDesordresCritere());
        $this->assertNotEmpty($dossierMessage->getDesordresPrecision());

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

    public function provideReference(): \Generator
    {
        yield 'Dossier avec l\'ancien formulaire 2024-01' => ['2024-01'];
        yield 'Dossier avec le nouveau formulaire 2024-02' => ['2024-02'];
    }
}
