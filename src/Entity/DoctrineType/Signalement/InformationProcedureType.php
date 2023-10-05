<?php

namespace App\Entity\DoctrineType\Signalement;

use App\Entity\Model\InformationProcedure;
use App\Factory\Signalement\InformationProcedureFactory;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class InformationProcedureType extends Type
{
    public const NAME = 'information_procedure';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof InformationProcedure) {
            throw new \Exception(sprintf('Only %s object is supported', InformationProcedure::class));
        }

        return json_encode($value->toArray());
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?InformationProcedure
    {
        if (null === $value) {
            return null;
        }

        return InformationProcedureFactory::createFromArray(json_decode($value, true));
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
