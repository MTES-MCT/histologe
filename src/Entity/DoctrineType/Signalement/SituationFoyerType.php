<?php

namespace App\Entity\DoctrineType\Signalement;

use App\Entity\Model\SituationFoyer;
use App\Factory\Signalement\SituationFoyerFactory;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class SituationFoyerType extends Type
{
    public const NAME = 'situation_foyer';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof SituationFoyer) {
            throw new \Exception(sprintf('Only %s object is supported', SituationFoyer::class));
        }

        return json_encode($value->toArray());
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        return SituationFoyerFactory::createFromArray(json_decode($value, true));
    }

    public function getName()
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
