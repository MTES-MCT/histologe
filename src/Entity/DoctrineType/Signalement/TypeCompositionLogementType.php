<?php

namespace App\Entity\DoctrineType\Signalement;

use App\Entity\Model\TypeCompositionLogement;
use App\Factory\Signalement\TypeCompositionLogementFactory;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class TypeCompositionLogementType extends Type
{
    public const NAME = 'type_composition_logement';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof TypeCompositionLogement) {
            throw new \Exception(sprintf('Only %s object is supported', TypeCompositionLogement::class));
        }

        return json_encode($value->toArray());
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        return TypeCompositionLogementFactory::createFromArray(json_decode($value, true));
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
