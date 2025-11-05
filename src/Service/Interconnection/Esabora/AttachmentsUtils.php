<?php

namespace App\Service\Interconnection\Esabora;

class AttachmentsUtils
{
    /**
     * @param array<int, array<string, mixed>> $piecesJointes
     */
    public static function computeTotalSize(array $piecesJointes): int
    {
        return array_reduce($piecesJointes, function ($carry, $item) {
            return $carry + ($item['documentSize'] ?? 0);
        }, 0);
    }
}
