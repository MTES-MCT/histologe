<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261008110338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new columns to handle oauth2 connection';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_312B3E1673F74AD4 ON partner');
        $this->addSql('ALTER TABLE partner ADD authentication_type VARCHAR(255) DEFAULT NULL, ADD oauth2_token_url VARCHAR(255) DEFAULT NULL, ADD oauth2_client_id VARCHAR(255) DEFAULT NULL, ADD oauth2_client_secret VARCHAR(255) DEFAULT NULL, CHANGE uuid uuid CHAR(36) NOT NULL, CHANGE competence competence LONGTEXT DEFAULT NULL, CHANGE idoss_token_expiration_date idoss_token_expiration_date DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner DROP authentication_type, DROP oauth2_token_url, DROP oauth2_client_id, DROP oauth2_client_secret, CHANGE uuid uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE competence competence LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE idoss_token_expiration_date idoss_token_expiration_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_312B3E1673F74AD4 ON partner (territory_id)');
    }
}
