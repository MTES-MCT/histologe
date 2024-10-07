<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241004142515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique index on created_from_id column in signalement table';
    }

    public function up(Schema $schema): void
    {
        $req_doublon = '
            SELECT id, created_from_id, statut
            FROM signalement
            WHERE created_from_id IN (
                SELECT created_from_id
                FROM signalement
                GROUP BY created_from_id
                HAVING COUNT(*) > 1
            )
            ORDER BY created_from_id ASC, statut desc
        ';
        $list = $this->connection->fetchAllAssociative($req_doublon);
        $createdFrom = 0;
        $lastId = 0;
        foreach ($list as $item) {
            // lorsqu'on a parcouru tous les doublons pour un même draft on garde uniquement le dernier de la liste (correspondant au statut le plus bas)
            if ($createdFrom !== $item['created_from_id']) {
                $this->keepSingleSignalementWithCreatedFrom($createdFrom, $lastId);
                $createdFrom = $item['created_from_id'];
            }
            $lastId = $item['id'];
        }
        $this->keepSingleSignalementWithCreatedFrom($createdFrom, $lastId);
        $this->addSql('ALTER TABLE signalement DROP INDEX IDX_F4B551143EA4CB4D, ADD UNIQUE INDEX UNIQ_F4B551143EA4CB4D (created_from_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP INDEX UNIQ_F4B551143EA4CB4D, ADD INDEX IDX_F4B551143EA4CB4D (created_from_id)');
    }

    private function keepSingleSignalementWithCreatedFrom(int $createdFrom, int $lastId): void
    {
        $this->addSql('UPDATE signalement SET created_from_id = NULL WHERE created_from_id = :created_from_id AND id != :id', [
            'created_from_id' => $createdFrom,
            'id' => $lastId,
        ]);
    }
}
