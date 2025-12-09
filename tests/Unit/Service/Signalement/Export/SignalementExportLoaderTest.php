<?php

namespace App\Tests\Unit\Service\Signalement\Export;

use App\Dto\SignalementExport;
use App\Entity\User;
use App\Manager\SignalementManager;
use App\Service\Signalement\Export\SignalementExportLoader;
use App\Tests\UserHelper;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SignalementExportLoaderTest extends TestCase
{
    use UserHelper;

    /** @dataProvider provideFileFormat */
    public function testLoad(string $formatExtension, string $formatCell): void
    {
        /** @var MockObject&SignalementManager */
        $signalementManager = $this->createMock(SignalementManager::class);
        $filters = [];
        $user = $this->getUserFromRole(User::ROLE_ADMIN);

        $signalementExports = [
            new SignalementExport(reference: '2023-01', createdAt: '31-03-2023', statut: 'nouveau'),
            new SignalementExport(reference: '2023-02', createdAt: '31-03-2023', statut: 'nouveau'),
        ];

        $signalementManager->expects($this->once())
            ->method('findSignalementAffectationIterable')
            ->with($user, $filters)
            ->willReturn($this->getSignalementExportGenerator($signalementExports));

        $loader = new SignalementExportLoader($signalementManager);
        $spreadsheet = $loader->load($user, $formatExtension, $filters, ['REFERENCE', 'CREATED_AT', 'STATUT']);
        $this->assertEquals('Référence', $spreadsheet->getActiveSheet()->getCell('A1')->getValue());
        $this->assertEquals('2023-01', $spreadsheet->getActiveSheet()->getCell('A2')->getValue());
        $style = $spreadsheet->getActiveSheet()->getStyle('A2');
        $formatCode = $style->getNumberFormat()->getFormatCode();
        $this->assertEquals('General', $formatCode);

        $this->assertEquals('Déposé le', $spreadsheet->getActiveSheet()->getCell('B1')->getValue());
        $style = $spreadsheet->getActiveSheet()->getStyle('B2');
        $formatCode = $style->getNumberFormat()->getFormatCode();
        $this->assertEquals($formatCell, $formatCode);
    }

    /**
     * @param array<SignalementExport> $signalements
     */
    private function getSignalementExportGenerator(array $signalements): \Generator
    {
        foreach ($signalements as $signalement) {
            yield $signalement;
        }
    }

    protected function provideFileFormat(): \Generator
    {
        yield 'export with xlsx' => ['xlsx', NumberFormat::FORMAT_DATE_DDMMYYYY];
        yield 'export with csv' => ['csv', 'General'];
    }
}
