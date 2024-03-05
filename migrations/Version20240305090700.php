<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

final class Version20240305090700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set code_suivi on Signalement when null';
    }

    public function up(Schema $schema): void
    {
        $signalements = $this->connection->fetchAllAssociative(
            'SELECT id FROM signalement WHERE code_suivi IS NULL'
        );

        foreach ($signalements as $signalement) {
            $id = $signalement['id'];
            $code = Uuid::v4()->toRfc4122();

            $this->addSql(
                'UPDATE signalement SET code_suivi = :code WHERE id = :id',
                ['code' => $code, 'id' => $id]
            );
        }
    }

    public function down(Schema $schema): void
    {
    }
}
