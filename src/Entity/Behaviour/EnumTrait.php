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

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function fromLabel(string $label): self
    {
        $key = self::getKeyFromLabel($label);
        if (null === $key) {
            throw new \ValueError("No case for label $label");
        }

        return self::from($key);
    }

    public static function tryFromLabel(string $label): ?self
    {
        $key = self::getKeyFromLabel($label);

        return null === $key ? null : self::tryFrom($key);
    }

    private static function getKeyFromLabel(string $label): ?string
    {
        $label = mb_trim($label);
        $key = array_search($label, self::getLabelList(), true);

        return (false === $key || !is_string($key)) ? null : $key;
    }
}
