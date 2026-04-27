<?php

namespace App\Repository\Behaviour;

use App\Entity\FailedEmail;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class FailedEmailCleaner implements EntityCleanerRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @throws Exception
     * @throws \DateMalformedStringException
     */
    public function cleanOlderThan(string $period = FailedEmail::EXPIRATION_PERIOD): int
    {
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['failed_email'])) {
            return 0;
        }

        $sql = 'DELETE FROM failed_email
                WHERE created_at < :created_at';

        return (int) $this->connection->executeStatement($sql, [
            'created_at' => (new \DateTimeImmutable($period))->format('Y-m-d H:i:s'),
        ]);
    }

    public function getClassName(): string
    {
        return 'failed_email';
    }
}
