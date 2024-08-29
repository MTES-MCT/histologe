<?php

namespace App\Tests\Unit\Service\Signalement\Export;

use App\Dto\SignalementExport;
use App\Entity\User;
use App\Manager\SignalementManager;
use App\Service\Signalement\Export\SignalementExportLoader;
use App\Tests\UserHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;

class SignalementExportLoaderTest extends TestCase
{
    use UserHelper;

    public function testLoad()
    {
        $signalementManager = $this->createMock(SignalementManager::class);
        $filters = [];
        $user = $this->getUserFromRole(User::ROLE_ADMIN);

        $signalementExports = [
            new SignalementExport('2023-01', '31-03-2023', 'nouveau'),
            new SignalementExport('2023-02', '31-03-2023', 'nouveau'),
        ];

        $signalementManager->expects($this->once())
            ->method('findSignalementAffectationIterable')
            ->with($user, $filters)
            ->willReturn($this->getSignalementExportGenerator($signalementExports));

        $loader = new SignalementExportLoader($signalementManager);
        $spreadsheet = $loader->load($user, $filters);
        $this->assertInstanceOf(Spreadsheet::class, $spreadsheet);
        $this->assertEquals('Référence', $spreadsheet->getActiveSheet()->getCell('A1')->getValue());
        $this->assertEquals('2023-01', $spreadsheet->getActiveSheet()->getCell('A2')->getValue());
    }

    private function getSignalementExportGenerator(array $signalements): \Generator
    {
        foreach ($signalements as $signalement) {
            yield $signalement;
        }
    }
}
