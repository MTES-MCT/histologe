<?php

declare(strict_types=1);

namespace App\Doctrine\Dql;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\SqlWalker;
use DoctrineExtensions\Query\Mysql\GroupConcat;

class PlatformGroupConcat extends GroupConcat
{
    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();
        if ($platform instanceof PostgreSQLPlatform) {
            $fields = [];
            foreach ($this->pathExp as $pathExp) {
                $fields[] = $pathExp->dispatch($sqlWalker);
            }
            $fieldSql = implode(' || ', $fields);
            $sepSql = $this->separator ? $sqlWalker->walkStringPrimary($this->separator) : "','";

            if ($this->isDistinct) {
                return sprintf('string_agg(DISTINCT %s, %s)', $fieldSql, $sepSql);
            }

            if ($this->orderBy) {
                return sprintf('string_agg(%s, %s %s)', $fieldSql, $sepSql, $sqlWalker->walkOrderByClause($this->orderBy));
            }

            return sprintf('string_agg(%s, %s)', $fieldSql, $sepSql);
        }

        return parent::getSql($sqlWalker);
    }
}
