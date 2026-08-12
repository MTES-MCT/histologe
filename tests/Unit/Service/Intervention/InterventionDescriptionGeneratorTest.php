<?php

namespace App\Tests\Unit\Service\Intervention;

use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Event\InterventionCreatedEvent;
use App\Event\InterventionRescheduledEvent;
use App\Event\InterventionUpdatedByEsaboraEvent;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Response\Model\DossierArreteSISH;
use App\Service\Intervention\InterventionDescriptionGenerator;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InterventionDescriptionGeneratorTest extends TestCase
{
    use FixturesHelper;

    #[DataProvider('provideVisiteIntervention')]
    public function testVisiteDescriptionOnInterventionCreated(
        Intervention $intervention,
        string $label,
        string $address,
        string $scheduledAt,
        string $partnerName,
    ): void {
        $description = InterventionDescriptionGenerator::generate(
            $intervention,
            InterventionCreatedEvent::NAME
        );

        $this->assertStringStartsWith($label, $description); // @phpstan-ignore-line
        $this->assertStringContainsString($address, $description);
        $this->assertStringContainsString($scheduledAt, $description);
        $this->assertStringContainsString($partnerName, $description);
    }

    public function testArreteDescriptionOnInterventionCreated(): void
    {
        $dossierArreteSISH = $this->getDossierArreteSISHCollectionResponse()->getCollection()[0];
        $description = InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH);

        $this->assertStringContainsString('2023/DD13/00664', $description, 'N° arrêté incorrect');
        $this->assertStringContainsString('14/06/2023', $description, 'Date arrêté incorrecte');
        $this->assertStringContainsString('n°2023/DD13/0010', $description, 'N° dossier incorrect');
        $this->assertStringContainsString('2023-DD13-00172', $description, 'N° main levée incorrect');
        $this->assertStringContainsString('01/07/2023', $description, 'Date de main levée incorrecte');

        $intervention = (new Intervention())
            ->setDetails('Test description')
            ->setType(InterventionType::ARRETE_PREFECTORAL);

        $this->assertEquals(
            'Test description',
            InterventionDescriptionGenerator::generate(
                $intervention,
                InterventionCreatedEvent::NAME
            )
        );
    }

    public function testArreteDescriptionOnInterventionUpdated(): void
    {
        $oldAdditionalInformation = [
            'arrete_numero' => '2023/DD13/00664',
            'arrete_type' => 'INSALUBRITE',
            'arrete_mainlevee_date' => '01/07/2023',
            'arrete_mainlevee_numero' => '2023-DD13-00172',
        ];
        $intervention = new Intervention()
            ->setScheduledAt(new \DateTimeImmutable('2023-06-14'))
            ->setAdditionalInformation($oldAdditionalInformation);

        // Test modification date et numéro arrêté
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00665');
        $dossierArreteSISH->method('getArreteDate')->willReturn('15/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('2023-DD13-00172');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($intervention, $dossierArreteSISH);
        $this->assertEquals('La date de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 15/06/2023<br>Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est 2023/DD13/00665', $description);

        // Test modification date seule arrêté
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('15/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('2023-DD13-00172');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($intervention, $dossierArreteSISH);
        $this->assertEquals('La date de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 15/06/2023', $description);

        // Test modification numéro seule arrêté
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00665');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('01/07/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('2023-DD13-00172');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($intervention, $dossierArreteSISH);
        $this->assertEquals('Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est 2023/DD13/00665', $description);

        // Test modification date et numéro mainlevée
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('02/07/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('2023-DD13-00173');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($intervention, $dossierArreteSISH);
        $this->assertEquals('La date de la mainlevée dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 02/07/2023<br>Le numéro de la mainlevée dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est 2023-DD13-00173', $description);

        // Test modification les deux (arrêté et mainlevée)
        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00665');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('02/07/2023');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('2023-DD13-00172');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteUpdated($intervention, $dossierArreteSISH);
        $this->assertEquals('Le numéro de l\'arrêté dans SI-Santé Habitat (SI-SH) a été modifié ; Le nouveau numéro est 2023/DD13/00665<br>La date de la mainlevée dans SI-Santé Habitat (SI-SH) a été modifiée ; La nouvelle date est 02/07/2023', $description);
    }

    public function testArreteDescriptionOnMainLeveeCreated(): void
    {
        $oldAdditionalInformation = [
            'arrete_numero' => '2023/DD13/00664',
            'arrete_type' => 'INSALUBRITE',
            'arrete_mainlevee_date' => null,
            'arrete_mainlevee_numero' => null,
        ];

        $dossierArreteSISH = $this->createMock(DossierArreteSISH::class);
        $dossierArreteSISH->method('getArreteNumero')->willReturn('2023/DD13/00664');
        $dossierArreteSISH->method('getArreteDate')->willReturn('14/06/2023');
        $dossierArreteSISH->method('getArreteMLDate')->willReturn('01/02/2026');
        $dossierArreteSISH->method('getArreteMLNumero')->willReturn('APML45K09O');
        $dossierArreteSISH->method('getDossNum')->willReturn('2023/DD13/0010');

        $description = InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH);
        $this->assertStringContainsString('Un arrêté de mainlevée APML45K09O du 01/02/2026 a été pris pour l\'arrêté 2023/DD13/00664 du 14/06/2023 dans le dossier de n°2023/DD13/0010.', $description);
    }

    public function testVisiteDescriptionOnInterventionUpdated(): void
    {
        $dateInFutur = (new \DateTimeImmutable())->add(new \DateInterval('P10D'))->setTimezone(new \DateTimeZone('Europe/Paris'));
        $intervention = $this->getIntervention(
            InterventionType::VISITE,
            $dateInFutur,
            Intervention::STATUS_PLANNED
        );

        $this->assertEquals(
            'La date de visite dans '.EsaboraSISHService::NAME_SI.' a été modifiée ; La nouvelle date est le '.$dateInFutur->format('d/m/Y').'.',
            InterventionDescriptionGenerator::generate(
                $intervention,
                InterventionUpdatedByEsaboraEvent::NAME
            )
        );
    }

    public function testVisiteControleDescriptionOnInterventionUpdated(): void
    {
        $dateInFutur = (new \DateTimeImmutable())
            ->add(new \DateInterval('P10D'))
            ->setTimezone(new \DateTimeZone('Europe/Paris'));
        $intervention = $this->getIntervention(
            InterventionType::VISITE_CONTROLE,
            $dateInFutur,
            Intervention::STATUS_PLANNED
        );

        $this->assertEquals(
            'La date de visite de contrôle dans '.EsaboraSISHService::NAME_SI.' a été modifiée ; La nouvelle date est le '.$dateInFutur->format('d/m/Y').'.',
            InterventionDescriptionGenerator::generate(
                $intervention,
                InterventionUpdatedByEsaboraEvent::NAME
            )
        );
    }

    public function testVisiteDescriptionOnUnknownEvent(): void
    {
        $this->assertNull(InterventionDescriptionGenerator::generate(
            (new Intervention())->setType(InterventionType::VISITE),
            InterventionRescheduledEvent::NAME
        ));
    }

    public static function provideVisiteIntervention(): \Generator
    {
        $fixturesHelper = new class {
            use FixturesHelper;
        };

        yield 'Visite de contrôle dans le passé' => [
            $fixturesHelper->getIntervention(
                InterventionType::VISITE_CONTROLE,
                new \DateTimeImmutable('2023-09-01'),
                Intervention::STATUS_DONE
            ),
            'Visite de contrôle réalisée :',
            '25 rue du test',
            '01/09/2023',
            'ARS',
        ];

        yield 'Visite dans le passé' => [
            $fixturesHelper->getIntervention(
                InterventionType::VISITE,
                new \DateTimeImmutable('2023-10-01'),
                Intervention::STATUS_DONE
            ),
            'Visite réalisée',
            '25 rue du test',
            '01/10/2023',
            'ARS',
        ];

        yield 'Visite dans le passé mais au status planned' => [
            $fixturesHelper->getIntervention(
                InterventionType::VISITE,
                new \DateTimeImmutable('2023-10-01'),
                Intervention::STATUS_PLANNED
            ),
            'Visite programmée',
            '25 rue du test',
            '01/10/2023',
            'ARS',
        ];

        $dateInFutur = (new \DateTimeImmutable())->add(new \DateInterval('P10D'))->setTimezone(new \DateTimeZone('Europe/Paris'));
        yield 'Visite de contrôle dans le futur' => [
            $fixturesHelper->getIntervention(
                InterventionType::VISITE_CONTROLE,
                $dateInFutur,
                Intervention::STATUS_PLANNED
            ),
            'Visite de contrôle programmée :',
            '25 rue du test',
            $dateInFutur->format('d/m/Y'),
            'ARS',
        ];

        yield 'Visite dans le futur à minuit' => [
            $fixturesHelper->getIntervention(
                InterventionType::VISITE,
                $dateInFutur->setTime(0, 0, 0),
                Intervention::STATUS_PLANNED
            ),
            'Visite programmée',
            '25 rue du test',
            $dateInFutur->format('d/m/Y').'.',
            'ARS',
        ];

        yield 'Visite dans le futur avec heure' => [
            $fixturesHelper->getIntervention(
                InterventionType::VISITE,
                $dateInFutur,
                Intervention::STATUS_PLANNED
            ),
            'Visite programmée',
            '25 rue du test',
            $dateInFutur->format('d/m/Y à H:i'),
            'ARS',
        ];
    }
}
