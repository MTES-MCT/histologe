<?php

namespace App\Repository\Behaviour;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class ApiUserTokenCleaner implements EntityCleanerRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @throws Exception
     * @throws \DateMalformedStringException
     */
    public function cleanOlderThan(string $period = '-30 days'): int
    {
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['api_user_token'])) {
            return 0;
        }

        $sql = 'DELETE FROM api_user_token
                WHERE expires_at < :expires_at';

        return (int) $this->connection->executeStatement($sql, [
            'expires_at' => (new \DateTimeImmutable($period))->format('Y-m-d H:i:s'),
        ]);
    }

    public function getClassName(): string
    {
        return 'api_user_token';
    }
}
