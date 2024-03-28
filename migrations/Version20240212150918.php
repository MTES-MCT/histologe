<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240212150918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add checksum to signalement_draft';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_draft ADD checksum VARCHAR(255) DEFAULT NULL');

        $signalements = $this->connection->fetchAllAssociative(
            'SELECT id, email_declarant, address_complete FROM signalement_draft'
        );

        foreach ($signalements as $signalement) {
            $id = $signalement['id'];
            $emailDeclarant = $signalement['email_declarant'];
            $addressComplete = $signalement['address_complete'];
            $hash = hash('sha256', $emailDeclarant.$addressComplete);

            $this->addSql(
                'UPDATE signalement_draft SET checksum = :hash WHERE id = :id',
                ['hash' => $hash, 'id' => $id]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_draft DROP checksum');
    }
}
