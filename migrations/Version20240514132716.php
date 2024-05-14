<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240514132716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add iDoss fields to partner';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner ADD is_idoss_active TINYINT(1) NOT NULL, ADD idoss_url VARCHAR(255) DEFAULT NULL, ADD idoss_token VARCHAR(255) DEFAULT NULL, ADD idoss_token_expiration_date DATETIME DEFAULT NULL, CHANGE insee insee JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner DROP is_idoss_active, DROP idoss_url, DROP idoss_token, DROP idoss_token_expiration_date, CHANGE insee insee LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
    }
}
