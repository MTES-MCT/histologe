<?php

namespace App\Service\Signalement\Export;

use App\Entity\User;
use App\Manager\SignalementManager;
use App\Messenger\Message\ListExportMessage;
use Doctrine\DBAL\Exception;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

readonly class SignalementExporter
{
    public const array DATE_COLUMNS = ['createdAt', 'dateVisite', 'modifiedAt', 'closedAt', 'infoProcedureBailDate'];

    public function __construct(
        private readonly SignalementManager $signalementManager,
    ) {
    }

    /**
     * @param ?array<mixed>  $filters
     * @param ?array<string> $selectedColumns
     *
     * @throws Exception
     */
    public function write(User $user, string $format, string $outputFilePath, ?array $filters, ?array $selectedColumns = null): void
    {
        if (ListExportMessage::FORMAT_CSV === $format) {
            $writer = new CsvWriter(new CsvOptions(FIELD_DELIMITER: SignalementExportHeader::SEPARATOR));
        } else {
            $writer = new XlsxWriter();
        }

        $writer->openToFile($outputFilePath);

        $keysToRemove = [];
        $headers = SignalementExportHeader::getHeaders();
        if (empty($selectedColumns)) {
            $selectedColumns = [];
        }
        $headers = $this->getHeadersWithSelectedColumns($headers, $keysToRemove, $selectedColumns);
        $writer->addRow($this->buildRow(array_values($headers)));

        $dateStyle = new Style(format: 'DD/MM/YYYY');
        foreach ($this->signalementManager->findSignalementAffectationIterable($user, $filters, $selectedColumns) as $signalementExportItem) {
            $rowArray = get_object_vars($signalementExportItem);

            $rowKeys = array_keys($rowArray);
            foreach ($keysToRemove as $numericIndex) {
                if (isset($rowKeys[$numericIndex])) {
                    unset($rowArray[$rowKeys[$numericIndex]]);
                }
            }

            $cells = [];
            foreach ($rowArray as $key => $value) {
                if (ListExportMessage::FORMAT_XLSX === $format && \in_array($key, self::DATE_COLUMNS, true) && !empty($value)) {
                    $dateTime = \DateTimeImmutable::createFromFormat('d/m/Y', $value);
                    if ($dateTime) {
                        $cells[] = Cell::fromValue($dateTime, $dateStyle);
                        continue;
                    }
                }
                $cells[] = Cell::fromValue($value);
            }

            $writer->addRow(new Row($cells));
        }

        $writer->close();
    }

    /**
     * @param array<string> $values
     */
    private function buildRow(array $values): Row
    {
        $cells = array_map(static fn ($v) => Cell::fromValue($v), $values);

        return new Row($cells);
    }

    /**
     * @param array<string> $headers
     * @param array<int>    $keysToRemove
     * @param array<string> $selectedColumns
     *
     * @return array<string>
     */
    private function getHeadersWithSelectedColumns(array $headers, array &$keysToRemove, array $selectedColumns): array
    {
        $selectableColumns = SignalementExportSelectableColumns::getColumns();
        foreach ($selectableColumns as $columnIndex => $selectableColumn) {
            $searchSelectedCol = array_search($columnIndex, $selectedColumns);
            // Unchecked col: delete from list
            if (false === $searchSelectedCol) {
                if ('s.geoloc' === $selectableColumn['export']) {
                    $lonIndex = $this->removeColFromHeaders('Longitude', $headers);
                    if (false !== $lonIndex) {
                        $keysToRemove[] = $lonIndex;
                    }
                    $latIndex = $this->removeColFromHeaders('Latitude', $headers);
                    if (false !== $latIndex) {
                        $keysToRemove[] = $latIndex;
                    }
                } else {
                    $colIndex = $this->removeColFromHeaders($selectableColumn['name'], $headers);
                    if (false !== $colIndex) {
                        $keysToRemove[] = $colIndex;
                    }
                }
            }
        }

        return $headers;
    }

    /**
     * @param array<string> $headers
     */
    private function removeColFromHeaders(string $colName, array &$headers): int|string|false
    {
        $indexToUnset = array_search($colName, $headers);
        if (false !== $indexToUnset && isset($headers[$indexToUnset])) {
            unset($headers[$indexToUnset]);

            return $indexToUnset;
        }

        return false;
    }
}
