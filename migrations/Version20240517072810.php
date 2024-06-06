<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240517072810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add iDoss fields to partner';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner ADD is_idoss_active TINYINT(1) NOT NULL, ADD idoss_url VARCHAR(255) DEFAULT NULL, ADD idoss_token VARCHAR(255) DEFAULT NULL, ADD idoss_token_expiration_date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner DROP is_idoss_active, DROP idoss_url, DROP idoss_token, DROP idoss_token_expiration_date');
    }
}
