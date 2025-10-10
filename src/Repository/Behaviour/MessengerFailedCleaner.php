<?php

namespace App\Repository\Behaviour;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class MessengerFailedCleaner implements EntityCleanerRepositoryInterface
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
        if (!$schemaManager->tablesExist(['messenger_messages'])) {
            return 0; // 0 message supprimé (pas de table messenger_messages en environnement de test)
        }

        $sql = 'DELETE FROM messenger_messages
                WHERE queue_name LIKE :queue
                  AND (delivered_at = :delivered_at OR created_at < :created_at)';

        return $this->connection->executeStatement($sql, [
            'queue' => 'failed%',
            'delivered_at' => '9999-12-31 23:59:59',
            'created_at' => (new \DateTimeImmutable($period))->format('Y-m-d H:i:s'),
        ]);
    }

    public function getClassName(): string
    {
        return 'messenger_messages';
    }
}
