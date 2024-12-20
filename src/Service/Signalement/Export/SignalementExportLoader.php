<?php

namespace App\Service\Signalement\Export;

use App\Entity\User;
use App\Manager\SignalementManager;
use Doctrine\DBAL\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

readonly class SignalementExportLoader
{
    public const int CHUNK_SIZE = 1000;

    public function __construct(
        private SignalementManager $signalementManager,
    ) {
    }

    /**
     * @throws Exception
     */
    public function load(User $user, ?array $filters, ?array $selectedColumns = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getCalculationEngine()->disableCalculationCache();
        $sheet = $spreadsheet->getActiveSheet();

        $keysToRemove = [];
        $headers = SignalementExportHeader::getHeaders();
        if (empty($selectedColumns)) {
            $selectedColumns = [];
        }
        $headers = $this->getHeadersWithSelectedColumns($headers, $keysToRemove, $selectedColumns);
        $sheet->fromArray([$headers]);

        $rowIndex = 2;
        foreach ($this->getDataChunks($user, $filters) as $chunk) {
            foreach ($chunk as $signalementExportItem) {
                $rowArray = get_object_vars($signalementExportItem);

                foreach ($keysToRemove as $index) {
                    unset($rowArray[$index]);
                }

                $sheet->fromArray([$rowArray], null, 'A'.$rowIndex++);
            }

            $spreadsheet->garbageCollect();
        }

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

    /**
     * @throws Exception
     */
    private function getDataChunks(User $user, ?array $filters): \Generator
    {
        $data = [];
        $counter = 0;

        foreach ($this->signalementManager->findSignalementAffectationIterable($user, $filters) as $row) {
            $data[] = $row;
            ++$counter;

            if ($counter >= self::CHUNK_SIZE) {
                yield $data; // return chunk by chunk
                $data = [];
                $counter = 0;
            }
        }

        if (!empty($data)) {
            yield $data; // return the last chunk
        }
    }
}
