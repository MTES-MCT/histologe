<?php

namespace App\Service\Signalement;

class SignalementInputValueMapper
{
    public const MAPPING_VALUES = [
        'oui' => true,
        'non' => false,
        'nsp' => null,
        'caf' => 'CAF',
        'msa' => 'MSA',
    ];

    public static function map(?string $value): mixed
    {
        return self::MAPPING_VALUES[$value] ?? null;
    }
}
