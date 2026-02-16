<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\CreationSource;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260213152012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add new column "creation_source" to "signalement" table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD creation_source VARCHAR(255) DEFAULT NULL');

        // add index on creation_source
        $this->addSql('CREATE INDEX idx_signalement_creation_source ON signalement (creation_source)');

        // mettre la valeur 'IMPORT' pour ceux qui sont à isImported = true
        $this->addSql("UPDATE signalement SET creation_source = '".CreationSource::IMPORT->value."' WHERE is_imported = true");

        // mettre la valeur 'API' pour ceux qui n'ont pas encore de source, pour qui created_by_id n'est pas null, et pour qui l'id user created_by_id correspond à un user a un rôle API
        $this->addSql("UPDATE signalement s SET creation_source = '".CreationSource::API->value."' WHERE s.creation_source IS NULL AND s.created_by_id IS NOT NULL AND s.created_by_id = (SELECT u.id FROM user u WHERE u.id = s.created_by_id AND JSON_CONTAINS(u.roles, '\"ROLE_API_USER\"'))");

        // mettre la valeur 'FORM_PRO' pour ceux qui n'ont pas encore de source et pour qui created_by_id n'est pas null
        $this->addSql("UPDATE signalement SET creation_source = '".CreationSource::FORM_PRO->value."' WHERE creation_source IS NULL AND created_by_id IS NOT NULL");

        // mettre la valeur 'FORM_USAGER_V2' pour ceux qui n'ont pas encore de source et pour qui created_from n'est pas null
        $this->addSql("UPDATE signalement SET creation_source = '".CreationSource::FORM_USAGER_V2->value."' WHERE creation_source IS NULL AND created_from_id IS NOT NULL");

        // mettre la valeur 'FORM_USAGER' pour ceux qui n'ont pas encore de source
        $this->addSql("UPDATE signalement SET creation_source = '".CreationSource::FORM_USAGER_V1->value."' WHERE creation_source IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP creation_source');
    }
}
