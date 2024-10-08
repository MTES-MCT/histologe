<?php

namespace App\Service\Signalement\Export;

use App\Entity\User;
use App\Manager\SignalementManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class SignalementExportLoader
{
    public function __construct(
        private SignalementManager $signalementManager,
        #[Autowire(env: 'FEATURE_EXPORT_CUSTOM')]
        private string $featureExportCustom
    ) {
    }

    public function load(User $user, ?array $filters, ?array $selectedColumns = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $keysToRemove = [];
        $headers = SignalementExportHeader::getHeaders();
        if (empty($selectedColumns)) {
            $selectedColumns = [];
        }
        if ($this->featureExportCustom) {
            $headers = $this->getHeadersWithSelectedColumns($headers, $keysToRemove, $selectedColumns);
        }
        $sheetData = [$headers];

        foreach ($this->signalementManager->findSignalementAffectationIterable($user, $filters) as $signalementExportItem) {
            $rowArray = get_object_vars($signalementExportItem);
            foreach ($keysToRemove as $index) {
                unset($rowArray[$index]);
            }
            $sheetData[] = $rowArray;
        }

        $sheet->fromArray($sheetData);

        return $spreadsheet;
    }

    private function getHeadersWithSelectedColumns(array $headers, array &$keysToRemove, array $selectedColumns): array
    {
        $selectableColumns = SignalementExportSelectableColumns::getColumns();
        foreach ($selectableColumns as $columnIndex => $selectableColumn) {
            $searchSelectedCol = array_search($columnIndex, $selectedColumns);
            // Unchecked col: delete from list
            if (false === $searchSelectedCol) {
                if ('geoloc' === $selectableColumn['export']) {
                    $this->removeColFromHeaders('Longitude', $headers);
                    $keysToRemove[] = 'longitude';
                    $this->removeColFromHeaders('Latitude', $headers);
                    $keysToRemove[] = 'latitude';
                } else {
                    $this->removeColFromHeaders($selectableColumn['name'], $headers);
                    $keysToRemove[] = $selectableColumn['export'];
                }
            }
        }

        return $headers;
    }

    private function removeColFromHeaders(string $colName, array &$headers): void
    {
        $indexToUnset = array_search($colName, $headers);
        if ($indexToUnset > 0 && isset($headers[$indexToUnset])) {
            unset($headers[$indexToUnset]);
        }
    }
}
