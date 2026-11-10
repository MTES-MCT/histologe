<?php

declare(strict_types=1);

namespace App\Doctrine\Dql;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\SqlWalker;
use DoctrineExtensions\Query\Mysql\Cast;

class PlatformCast extends Cast
{
    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();
        if ($platform instanceof PostgreSQLPlatform) {
            $expr = $sqlWalker->walkSimpleArithmeticExpression($this->fieldIdentifierExpression);
            $type = strtoupper($this->castingTypeExpression);

            if (\in_array($type, ['UNSIGNED', 'SIGNED', 'INTEGER', 'INT'], true)) {
                return sprintf('mysql_cast_int(%s)', $expr);
            }

            return sprintf('CAST(%s AS %s)', $expr, $this->castingTypeExpression);
        }

        return parent::getSql($sqlWalker);
    }
}
