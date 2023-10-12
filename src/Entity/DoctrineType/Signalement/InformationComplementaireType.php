<?php

namespace App\Entity\DoctrineType\Signalement;

use App\Entity\Model\InformationComplementaire;
use App\Factory\Signalement\InformationComplementaireFactory;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class InformationComplementaireType extends Type
{
    public const NAME = 'information_complementaire';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof InformationComplementaire) {
            throw new \Exception(sprintf('Only %s object is supported', InformationComplementaire::class));
        }

        return json_encode($value->toArray());
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?InformationComplementaire
    {
        if (null === $value) {
            return null;
        }

        return InformationComplementaireFactory::createFromArray(json_decode($value, true));
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
