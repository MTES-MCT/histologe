<?php

namespace App\Tests\Functional\Repository\Query\Statistics;

use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\TravauxMiseEnConformite;
use App\Entity\Signalement;
use App\Repository\Query\Statistics\MotifClotureStatisticsQuery;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MotifClotureStatisticsQueryTest extends KernelTestCase
{
    private MotifClotureStatisticsQuery $motifClotureStatisticsQuery;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->motifClotureStatisticsQuery = static::getContainer()->get(MotifClotureStatisticsQuery::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    #[DataProvider('provideClotureValues')]
    public function testCountSignalementResolu(
        SignalementStatus $statut,
        MotifCloture $motifCloture,
        ?TravauxMiseEnConformite $travauxMiseEnConformite,
        ?TravauxMiseEnConformite $travauxMiseEnConformiteUsager,
        bool $isImported,
        bool $isCounted,
    ): void {
        // Le signalement 2023-1 est importé dans les fixtures, il n'est donc pas compté initialement
        $initialCount = $this->motifClotureStatisticsQuery->countSignalementResolu();

        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2023-1']);
        $signalement
            ->setIsImported($isImported)
            ->setStatut($statut)
            ->setClosedAt(new \DateTimeImmutable('-1 month'))
            ->setMotifCloture($motifCloture)
            ->setTravauxMiseEnConformite($travauxMiseEnConformite)
            ->setTravauxMiseEnConformiteUsager($travauxMiseEnConformiteUsager);
        $this->entityManager->flush();

        $this->assertSame(
            $initialCount + ($isCounted ? 1 : 0),
            $this->motifClotureStatisticsQuery->countSignalementResolu()
        );
    }

    public static function provideClotureValues(): \Generator
    {
        yield 'v1 - travaux faits sans précision' => [SignalementStatus::CLOSED, MotifCloture::TRAVAUX_FAITS_OU_EN_COURS, null, null, false, true];
        yield 'v1 - péril sans précision' => [SignalementStatus::CLOSED, MotifCloture::PERIL, null, null, false, true];
        yield 'v1 - avec précision travaux (incohérent)' => [SignalementStatus::CLOSED, MotifCloture::INSALUBRITE, TravauxMiseEnConformite::OUI, null, false, false];

        yield 'v2 - travaux OUI, usager sans réponse' => [SignalementStatus::CLOSED, MotifCloture::LOGEMENT_MIS_EN_CONFORMITE, TravauxMiseEnConformite::OUI, null, false, true];
        yield 'v2 - travaux EN_COURS, usager sans réponse' => [SignalementStatus::CLOSED, MotifCloture::LOGEMENT_MIS_EN_CONFORMITE, TravauxMiseEnConformite::EN_COURS, null, false, true];
        yield 'v2 - travaux EN_COURS, usager OUI' => [SignalementStatus::CLOSED, MotifCloture::LOGEMENT_MIS_EN_CONFORMITE, TravauxMiseEnConformite::EN_COURS, TravauxMiseEnConformite::OUI, false, true];
        yield 'v2 - travaux EN_COURS, usager EN_COURS' => [SignalementStatus::CLOSED, MotifCloture::LOGEMENT_MIS_EN_CONFORMITE, TravauxMiseEnConformite::EN_COURS, TravauxMiseEnConformite::EN_COURS, false, true];
        yield 'v2 - travaux EN_COURS, usager NE_SAIT_PAS' => [SignalementStatus::CLOSED, MotifCloture::LOGEMENT_MIS_EN_CONFORMITE, TravauxMiseEnConformite::EN_COURS, TravauxMiseEnConformite::NE_SAIT_PAS, false, true];
        yield 'v2 - travaux EN_COURS, usager NON' => [SignalementStatus::CLOSED, MotifCloture::LOGEMENT_MIS_EN_CONFORMITE, TravauxMiseEnConformite::EN_COURS, TravauxMiseEnConformite::NON, false, false];
        yield 'v2 - travaux NON' => [SignalementStatus::CLOSED, MotifCloture::DEPART_OCCUPANT, TravauxMiseEnConformite::NON, null, false, false];
        yield 'v2 - travaux NE_SAIT_PAS' => [SignalementStatus::CLOSED, MotifCloture::AUTRE, TravauxMiseEnConformite::NE_SAIT_PAS, null, false, false];

        yield 'signalement importé' => [SignalementStatus::CLOSED, MotifCloture::LOGEMENT_MIS_EN_CONFORMITE, TravauxMiseEnConformite::OUI, null, true, false];
        yield 'signalement non clôturé' => [SignalementStatus::ACTIVE, MotifCloture::LOGEMENT_MIS_EN_CONFORMITE, TravauxMiseEnConformite::OUI, null, false, false];
    }
}
