<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProcedureType;
use App\Entity\Intervention;
use App\EventListener\InterventionEditedListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;

class InterventionEditedListenerTest extends TestCase
{
    /**
     * @param array<string, array{mixed, mixed}> $changeSet
     */
    private function createPreUpdateArgs(object $entity, array $changeSet): PreUpdateEventArgs
    {
        $em = $this->createMock(EntityManagerInterface::class);

        return new PreUpdateEventArgs($entity, $em, $changeSet);
    }

    public function testNoDiffWhenInterventionIsNotSupported(): void
    {
        $intervention = (new Intervention())
            ->setStatus(Intervention::STATUS_PLANNED)
            ->setType(InterventionType::VISITE);

        $changeSet = [
            'details' => ['foo', 'bar'],
        ];

        $args = $this->createPreUpdateArgs($intervention, $changeSet);
        $listener = new InterventionEditedListener();
        $listener->preUpdate($intervention, $args);

        $this->assertNull($intervention->getConclusionVisiteEditedAt());
        $this->assertEmpty($intervention->getChangesForMail());
    }

    public function testNoDiffWhenAddLineEnding(): void
    {
        $intervention = (new Intervention())
            ->setStatus(Intervention::STATUS_DONE)
            ->setType(InterventionType::VISITE);

        $old = "Ligne 1\nLigne 2";
        $new = "Ligne 1\r\nLigne 2\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n";

        $changeSet = [
            'details' => [$old, $new],
        ];

        $args = $this->createPreUpdateArgs($intervention, $changeSet);
        $listener = new InterventionEditedListener();
        $listener->preUpdate($intervention, $args);

        $this->assertNull($intervention->getConclusionVisiteEditedAt());
        $this->assertEmpty($intervention->getChangesForMail());
    }

    public function testNoDiffWhenProcedureIsSame(): void
    {
        $intervention = (new Intervention())
            ->setStatus(Intervention::STATUS_DONE)
            ->setType(InterventionType::VISITE);

        $old = [ProcedureType::INSALUBRITE->value, ProcedureType::RSD->value];
        $new = [ProcedureType::INSALUBRITE->value, ProcedureType::RSD->value];

        $args = $this->createPreUpdateArgs($intervention, [
            'concludeProcedure' => [$old, $new],
        ]);

        $listener = new InterventionEditedListener();
        $listener->preUpdate($intervention, $args);

        $this->assertNull($intervention->getConclusionVisiteEditedAt());
        $this->assertEmpty($intervention->getChangesForMail());
    }

    public function testNoDiffWhenProcedureIsSameNotSorted(): void
    {
        $intervention = (new Intervention())
            ->setStatus(Intervention::STATUS_DONE)
            ->setType(InterventionType::VISITE);

        $old = [ProcedureType::INSALUBRITE->value, ProcedureType::RSD->value];
        $new = [ProcedureType::RSD->value, ProcedureType::INSALUBRITE->value];

        $args = $this->createPreUpdateArgs($intervention, [
            'concludeProcedure' => [$old, $new],
        ]);

        $listener = new InterventionEditedListener();
        $listener->preUpdate($intervention, $args);

        $this->assertNull($intervention->getConclusionVisiteEditedAt());
        $this->assertEmpty($intervention->getChangesForMail());
    }

    public function testDiffWhenDetailsUpdated(): void
    {
        $intervention = (new Intervention())
            ->setStatus(Intervention::STATUS_DONE)
            ->setType(InterventionType::VISITE);

        $old = 'Bonjour, foo bar.';
        $new = 'Bonjour, foo baré.';

        $changeSet = [
            'details' => [$old, $new],
        ];

        $args = $this->createPreUpdateArgs($intervention, $changeSet);
        $listener = new InterventionEditedListener();
        $listener->preUpdate($intervention, $args);

        $this->assertNotNull($intervention->getConclusionVisiteEditedAt());

        $changes = $intervention->getChangesForMail();
        $this->assertIsArray($changes);
        $this->assertArrayHasKey('details', $changes);

        $this->assertSame('Bonjour, foo bar.', $changes['details']['old']);
        $this->assertSame('Bonjour, foo baré.', $changes['details']['new']);
    }

    public function testDiffWhenProcedureIsUpdated(): void
    {
        $intervention = (new Intervention())
            ->setStatus(Intervention::STATUS_DONE)
            ->setType(InterventionType::VISITE);

        $old = [ProcedureType::INSALUBRITE->value];
        $new = [ProcedureType::INSALUBRITE->value, ProcedureType::RSD->value];

        $args = $this->createPreUpdateArgs($intervention, [
            'concludeProcedure' => [$old, $new],
        ]);

        $listener = new InterventionEditedListener();
        $listener->preUpdate($intervention, $args);

        $this->assertNotNull($intervention->getConclusionVisiteEditedAt());
        $this->assertNotEmpty($intervention->getChangesForMail());

        $expectedOld = 'Insalubrité';
        $expectedNew = 'Infraction RSD, Insalubrité';
        $changes = $intervention->getChangesForMail();
        $this->assertSame($expectedOld, $changes['concludeProcedure']['old']);
        $this->assertSame($expectedNew, $changes['concludeProcedure']['new']);
    }

    public function testDiffWhenDetailAndProceduresAreUpdated(): void
    {
        $intervention = (new Intervention())
            ->setStatus(Intervention::STATUS_DONE)
            ->setType(InterventionType::VISITE);

        $args = $this->createPreUpdateArgs($intervention, [
            'concludeProcedure' => [[ProcedureType::AUTRE->value], [ProcedureType::MISE_EN_SECURITE_PERIL->value]],
            'details' => ['Bonjour, foo bar ée.', 'Bonjour, foo bar éés.'],
        ]);

        $listener = new InterventionEditedListener();
        $listener->preUpdate($intervention, $args);

        $this->assertNotNull($intervention->getConclusionVisiteEditedAt());
        $this->assertNotEmpty($intervention->getChangesForMail());
        $this->assertArrayHasKey('details', $intervention->getChangesForMail());
        $this->assertArrayHasKey('concludeProcedure', $intervention->getChangesForMail());
    }

    public function testDiffNoOldValue(): void
    {
        $intervention = (new Intervention())
            ->setStatus(Intervention::STATUS_DONE)
            ->setType(InterventionType::VISITE_CONTROLE);

        $args = $this->createPreUpdateArgs($intervention, [
            'concludeProcedure' => [null, [ProcedureType::RSD->value, ProcedureType::INSALUBRITE->value]],
            'details' => [null, 'Bonjour, foo bar éés.'],
        ]);

        $listener = new InterventionEditedListener();
        $listener->preUpdate($intervention, $args);

        $this->assertNotNull($intervention->getConclusionVisiteEditedAt());
        $this->assertNotEmpty($intervention->getChangesForMail()['details']['new']);
        $this->assertNotEmpty($intervention->getChangesForMail()['concludeProcedure']['new']);
    }
}
