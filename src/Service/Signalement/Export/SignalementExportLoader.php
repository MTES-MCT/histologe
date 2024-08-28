<?php

namespace App\Service\Signalement\Export;

use App\Controller\Back\ExportSignalementController;
use App\Entity\User;
use App\Manager\SignalementManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
            if ($searchSelectedCol === false) {
                $indexToUnset = array_search($selectableColumn['name'], $headers);
                array_push($keysToRemove, $selectableColumn['export']);
                unset($headers[$indexToUnset]);
            }
        }
        array_push($sheetData, $headers);

        foreach ($this->signalementManager->findSignalementAffectationIterable($user, $filters) as $signalementExportItem) {
            $rowArray = get_object_vars($signalementExportItem);
            foreach ($keysToRemove as $index) {
                unset($rowArray[$index]);
            }
            array_push($sheetData, $rowArray);
        }

        $sheet->fromArray($sheetData);

        return $spreadsheet;
    }
}
