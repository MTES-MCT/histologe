<?php

namespace App\Service\Signalement\Export;

use App\Controller\Back\ExportSignalementController;
use App\Entity\User;
use App\Manager\SignalementManager;

class SignalementExportLoader
{
    public function __construct(private readonly SignalementManager $signalementManager)
    {
    }

    public function load(User $user, ?array $filters, ?array $selectedColumns = null): void
    {
        $handle = fopen('php://output', 'w');

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
        fputcsv($handle, $headers, SignalementExportHeader::SEPARATOR);

        foreach ($this->signalementManager->findSignalementAffectationIterable($user, $filters) as $signalementExportItem) {
            $rowArray = get_object_vars($signalementExportItem);
            foreach ($keysToRemove as $index) {
                unset($rowArray[$index]);
            }
            fputcsv($handle, $rowArray, SignalementExportHeader::SEPARATOR);
        }
        fclose($handle);
    }
}
