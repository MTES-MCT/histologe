<?php

namespace App\Service\Signalement\Export;

use App\Controller\Back\ExportSignalementController;
use App\Entity\User;
use App\Manager\SignalementManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SignalementExportLoader
{
    public function __construct(
        private readonly SignalementManager $signalementManager,
    ) {
    }

    public function load(User $user, ?array $filters, ?array $selectedColumns = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheetData = [];
        $keysToRemove = [];
        $headers = SignalementExportHeader::getHeaders();
        foreach (ExportSignalementController::SELECTABLE_COLS as $columnIndex => $selectableColumn) {
            $searchSelectedCol = array_search($columnIndex, $selectedColumns);
            if (false === $searchSelectedCol) {
                $indexToUnset = array_search($selectableColumn['name'], $headers);
                $keysToRemove[] = $selectableColumn['export'];
                unset($headers[$indexToUnset]);
            }
        }
        $sheetData[] = $headers;

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
}
