<?php

namespace App\Tests\Unit\Service\Intervention;

use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Event\InterventionCreatedEvent;
use App\Event\InterventionRescheduledEvent;
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
        string $partnerName
    ): void {
        $description = InterventionDescriptionGenerator::generate($intervention, InterventionCreatedEvent::NAME);

        $this->assertStringStartsWith($label, $description);
        $this->assertStringContainsString($address, $description);
        $this->assertStringContainsString($scheduledAt, $description);
        $this->assertStringContainsString($partnerName, $description);
    }

    public function testArreteDescriptionOnInterventionCreated(): void
    {
        $dossierArreteSISH = $this->getDossierArreteSISHCollectionResponse()->getCollection()[0];
        $description = InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH);

        $this->assertStringContainsString('Arrêté L.511-11', $description, 'Type arrêté incorrecte');
        $this->assertStringContainsString('n°2023/DD13/00664', $description, 'N° arrêté incorrecte');
        $this->assertStringContainsString('14/06/2023', $description, 'Date arrêté incorrecte');
        $this->assertStringContainsString('n°2023/DD13/0010', $description, 'N° dossier incorrecte');
        $this->assertStringContainsString('n°2023-DD13-00172', $description, 'N° main levée incorrecte');
        $this->assertStringContainsString('01/07/2023', $description, 'Date de main levée incorrecte');

        $intervention = (new Intervention())
            ->setDetails('Test description')
            ->setType(InterventionType::ARRETE_PREFECTORAL);

        $this->assertEquals('Test description',
            InterventionDescriptionGenerator::generate(
                $intervention,
                InterventionCreatedEvent::NAME
            )
        );
    }

    public function testVisiteDescriptionOnUnknownEvent()
    {
        $this->assertNull(InterventionDescriptionGenerator::generate(
            (new Intervention())->setType(InterventionType::VISITE),
            InterventionRescheduledEvent::NAME
        ));
    }

    public function provideVisiteIntervention(): \Generator
    {
        yield 'Visite de contrôle' => [
            $this->getIntervention(
                InterventionType::VISITE_CONTROLE,
                new \DateTimeImmutable('2023-09-01'),
                Intervention::STATUS_PLANNED), 'Visite de contrôle programmée :', '25 rue du test', '01/09/2023', 'ARS', ];

        yield 'Visite' => [
            $this->getIntervention(
                InterventionType::VISITE,
                new \DateTimeImmutable('2023-10-01'),
                Intervention::STATUS_PLANNED), 'Visite programmée', '25 rue du test', '01/10/2023', 'ARS', ];
    }
}
