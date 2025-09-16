<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250916135450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email_delivery_issue table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE email_delivery_issue (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(255) NOT NULL,
            event VARCHAR(255) NOT NULL,
            reason VARCHAR(255) DEFAULT NULL,
            payload JSON NOT NULL,
            created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE partner ADD email_delivery_issue_id INT DEFAULT NULL AFTER email');
        $this->addSql('ALTER TABLE partner ADD CONSTRAINT FK_312B3E166A105B27 FOREIGN KEY (email_delivery_issue_id) REFERENCES email_delivery_issue (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_312B3E166A105B27 ON partner (email_delivery_issue_id)');
        $this->addSql('ALTER TABLE user ADD email_delivery_issue_id INT DEFAULT NULL AFTER email');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6496A105B27 FOREIGN KEY (email_delivery_issue_id) REFERENCES email_delivery_issue (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8D93D6496A105B27 ON user (email_delivery_issue_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner DROP FOREIGN KEY FK_312B3E166A105B27');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6496A105B27');
        $this->addSql('DROP TABLE email_delivery_issue');
        $this->addSql('DROP INDEX IDX_312B3E166A105B27 ON partner');
        $this->addSql('ALTER TABLE partner DROP email_delivery_issue_id');
        $this->addSql('DROP INDEX IDX_8D93D6496A105B27 ON user');
        $this->addSql('ALTER TABLE user DROP email_delivery_issue_id');
    }
}
