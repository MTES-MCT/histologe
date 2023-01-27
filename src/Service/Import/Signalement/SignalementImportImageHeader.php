<?php

namespace App\Service\Import\Signalement;

class SignalementImportImageHeader
{
    public const COLUMN_ID_ENREGISTREMENT_ATTACHMENT = 'id_EnregistrementAttachment';
    public const COLUMN_ID_ENREGISTREMENT = 'id_Enregistrement';
    public const COLUMN_FILENAME = 'sAttachFileName';
    public const COLUMNS_LIST = [
        self::COLUMN_ID_ENREGISTREMENT_ATTACHMENT,
        self::COLUMN_ID_ENREGISTREMENT,
        self::COLUMN_FILENAME,
    ];
}
