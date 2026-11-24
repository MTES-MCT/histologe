<?php

declare(strict_types=1);

namespace App\Doctrine\Dql;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\SqlWalker;
use Scienta\DoctrineJsonFunctions\Query\AST\Functions\Mysql\JsonContains as MysqlJsonContains;

class PlatformJsonContains extends MysqlJsonContains
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

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        $this->validatePlatform($sqlWalker);

        $platform = $sqlWalker->getConnection()->getDatabasePlatform();
        if ($platform instanceof PostgreSQLPlatform) {
            $args = [];
            foreach ($this->parsedArguments as $parsedArgument) {
                if (null === $parsedArgument) {
                    $args[] = 'NULL';
                } else {
                    $args[] = $parsedArgument->dispatch($sqlWalker);
                }
            }

            return sprintf('(CASE WHEN (%s::jsonb @> %s::jsonb) THEN 1 ELSE 0 END)', $args[0], $args[1]);
        }

        return parent::getSql($sqlWalker);
    }
}
