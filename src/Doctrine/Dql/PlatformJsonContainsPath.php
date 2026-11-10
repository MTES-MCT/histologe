<?php

declare(strict_types=1);

namespace App\Doctrine\Dql;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\SqlWalker;
use Scienta\DoctrineJsonFunctions\Query\AST\Functions\Mysql\JsonContainsPath as MysqlJsonContainsPath;

class PlatformJsonContainsPath extends MysqlJsonContainsPath
{
    #[\Override]
    protected function validatePlatform(SqlWalker $sqlWalker): void
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();
        if ($platform instanceof PostgreSQLPlatform) {
            return;
        }

        parent::validatePlatform($sqlWalker);
    }
}
