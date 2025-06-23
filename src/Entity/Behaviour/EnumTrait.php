<?php

namespace App\Entity\Behaviour;

trait EnumTrait
{
    /**
     * @return array<string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function fromLabel(string $label): self
    {
        $key = self::getKeyFromLabel($label);

        return self::from($key);
    }

    public static function tryFromLabel(string $label): ?self
    {
        $key = self::getKeyFromLabel($label);

        return self::tryFrom($key);
    }

    private static function getKeyFromLabel(string $label): string|false
    {
        $label = mb_trim($label);

        return array_search($label, self::getLabelList());
    }
}
