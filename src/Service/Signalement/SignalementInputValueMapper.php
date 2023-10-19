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

    public function map(string $value)
    {
        return self::MAPPING_VALUES[$value] ?? null;
    }
}
