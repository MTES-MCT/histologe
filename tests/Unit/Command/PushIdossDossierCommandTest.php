<?php

namespace App\Tests\Unit\Command;

use App\Command\PushIdossDossierCommand;
use App\Entity\Enum\PartnerType;
use App\Messenger\InterconnectionBus;
use App\Repository\AffectationRepository;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PushIdossDossierCommandTest extends TestCase
{
    use FixturesHelper;

    private MockObject|AffectationRepository $affectationRepository;
    private MockObject|InterconnectionBus $interconnectionBus;

    protected function setUp(): void
    {
        $this->affectationRepository = $this->createMock(AffectationRepository::class);
        $this->interconnectionBus = $this->createMock(InterconnectionBus::class);
        parent::setUp();
    }

    public function testExecuteWithUuidOptionSuccess(): void
    {
        $affectation = $this->getAffectation(PartnerType::COMMUNE_SCHS);
        $affectation->getPartner()
            ->setNom('Partenaire 13-05 ESABORA SCHS')
            ->setIsIdossActive(true)
            ->setIdossUrl('https://idoss-partenaire-13-05.fr');
        $affectations = [
            $affectation,
        ];

        $this->affectationRepository
            ->expects($this->once())
            ->method('findAffectationSubscribedToIdoss')
            ->with('uuid-test')
            ->willReturn($affectations);

        $this->interconnectionBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($affectation));

        $command = new PushIdossDossierCommand(
            $this->affectationRepository,
            $this->interconnectionBus
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'uuid' => 'uuid-test',
        ]);

        $this->assertStringContainsString('poussé vers iDoss', $commandTester->getDisplay());
    }

    public function testExecuteWithUuidOptionNoAffectation(): void
    {
        $this->affectationRepository
            ->expects($this->once())
            ->method('findAffectationSubscribedToIdoss')
            ->with('uuid-inconnu')
            ->willReturn([]);

        $this->interconnectionBus
            ->expects($this->never())
            ->method('dispatch');

        $command = new PushIdossDossierCommand(
            $this->affectationRepository,
            $this->interconnectionBus
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'uuid' => 'uuid-inconnu',
        ]);

        $this->assertStringContainsString('Aucun partenaire iDoss affecté à ce signalement', $commandTester->getDisplay());
    }

    public function testExecuteWithoutUuidArgument(): void
    {
        $command = new PushIdossDossierCommand(
            $this->affectationRepository,
            $this->interconnectionBus
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString('L\'argument uuid est obligatoire', $commandTester->getDisplay());
    }
}
