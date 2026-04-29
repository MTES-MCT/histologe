<?php

namespace App\Tests\Unit\Service\Signalement\Export;

use App\Dto\SignalementExport;
use App\Entity\User;
use App\Manager\SignalementManager;
use App\Service\Signalement\Export\SignalementExporter;
use App\Service\Signalement\Export\SignalementExportHeader;
use App\Tests\UserHelper;
use OpenSpout\Reader\CSV\Options as CsvReaderOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SignalementExporterTest extends TestCase
{
    use UserHelper;

    /** @dataProvider provideFileFormat */
    public function testLoad(string $format): void
    {
        /** @var MockObject&SignalementManager */
        $signalementManager = $this->createMock(SignalementManager::class);
        $filters = [];
        $user = $this->getUserFromRole(User::ROLE_ADMIN);
        $selectedColumns = ['REFERENCE', 'CREATED_AT', 'STATUT'];

        $signalementExports = [
            new SignalementExport(reference: '2023-01', createdAt: '31/03/2023', statut: 'nouveau'),
            new SignalementExport(reference: '2023-02', createdAt: '31/03/2023', statut: 'nouveau'),
        ];

        $signalementManager->expects($this->once())
            ->method('findSignalementAffectationIterable')
            ->with($user, $filters, $selectedColumns)
            ->willReturn($this->getSignalementExportGenerator($signalementExports));

        $tmpFile = sys_get_temp_dir().'/export_test_'.uniqid().'.'.$format;

        $loader = new SignalementExporter($signalementManager);
        $loader->write($user, $format, $tmpFile, $filters, $selectedColumns);

        $rows = $this->readFile($tmpFile, $format);
        unlink($tmpFile);

        $this->assertEquals('Référence', $rows[0][0]);
        $this->assertEquals('Déposé le', $rows[0][1]);
        $this->assertEquals('2023-01', $rows[1][0]);

        if ('xlsx' === $format) {
            $this->assertInstanceOf(\DateTimeInterface::class, $rows[1][1]);
        } else {
            $this->assertEquals('31/03/2023', $rows[1][1]);
        }
    }

    /**
     * @return array<array<mixed>>
     */
    private function readFile(string $filePath, string $format): array
    {
        $rows = [];

        if ('csv' === $format) {
            $reader = new CsvReader(new CsvReaderOptions(FIELD_DELIMITER: SignalementExportHeader::SEPARATOR));
        } else {
            $reader = new XlsxReader();
        }

        $reader->open($filePath);
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowValues = [];
                foreach ($row->cells as $cell) {
                    $rowValues[] = $cell->getValue();
                }
                $rows[] = $rowValues;
            }
            break;
        }
        $reader->close();

        return $rows;
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
        yield 'export with xlsx' => ['xlsx'];
        yield 'export with csv' => ['csv'];
    }
}
