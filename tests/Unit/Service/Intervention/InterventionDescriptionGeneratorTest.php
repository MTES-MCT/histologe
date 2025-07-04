<?php

namespace App\Tests\Unit\Service\Intervention;

use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Event\InterventionCreatedEvent;
use App\Event\InterventionRescheduledEvent;
use App\Event\InterventionUpdatedByEsaboraEvent;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Intervention\InterventionDescriptionGenerator;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class InterventionDescriptionGeneratorTest extends TestCase
{
    use FixturesHelper;

    /**
     * @dataProvider provideVisiteIntervention
     */
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

        $this->assertStringStartsWith($label, $description);
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

    public function testVisiteDescriptionOnInterventionUpdated(): void
    {
        $dateInFutur = (new \DateTimeImmutable())->add(new \DateInterval('P10D'));
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
        $dateInFutur = (new \DateTimeImmutable())->add(new \DateInterval('P10D'));
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

    public function provideVisiteIntervention(): \Generator
    {
        yield 'Visite de contrôle dans le passé' => [
            $this->getIntervention(
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
            $this->getIntervention(
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
            $this->getIntervention(
                InterventionType::VISITE,
                new \DateTimeImmutable('2023-10-01'),
                Intervention::STATUS_PLANNED
            ),
            'Visite programmée',
            '25 rue du test',
            '01/10/2023',
            'ARS',
        ];

        $dateInFutur = (new \DateTimeImmutable())->add(new \DateInterval('P10D'));
        yield 'Visite de contrôle dans le futur' => [
            $this->getIntervention(
                InterventionType::VISITE_CONTROLE,
                $dateInFutur,
                Intervention::STATUS_PLANNED
            ),
            'Visite de contrôle programmée :',
            '25 rue du test',
            $dateInFutur->format('d/m/Y'),
            'ARS',
        ];

        yield 'Visite dans le futur' => [
            $this->getIntervention(
                InterventionType::VISITE,
                $dateInFutur,
                Intervention::STATUS_PLANNED
            ),
            'Visite programmée',
            '25 rue du test',
            $dateInFutur->format('d/m/Y'),
            'ARS',
        ];
    }
}
