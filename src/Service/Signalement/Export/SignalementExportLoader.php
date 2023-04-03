<?php

namespace App\Service\Signalement\Export;

use App\Entity\User;
use App\Manager\SignalementManager;

class SignalementExportLoader
{
    public function __construct(private readonly SignalementManager $signalementManager)
    {
    }

    public function load(User $user, array $filters): void
    {
        $handle = fopen('php://output', 'w');
        fputcsv($handle, SignalementExportHeader::getHeaders(), SignalementExportHeader::SEPARATOR);
        foreach ($this->signalementManager->findSignalementAffectationIterable($user, $filters) as $signalementExportItem) {
            fputcsv($handle, get_object_vars($signalementExportItem), SignalementExportHeader::SEPARATOR);
        }
        fclose($handle);
    }
}
