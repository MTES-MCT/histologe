<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251028151831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove relation manyToOne join between  user and email_delivery_issue';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6496A105B27');
        $this->addSql('DROP INDEX IDX_8D93D6496A105B27 ON user');
        $this->addSql('ALTER TABLE user DROP email_delivery_issue_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD email_delivery_issue_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6496A105B27 FOREIGN KEY (email_delivery_issue_id) REFERENCES email_delivery_issue (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8D93D6496A105B27 ON user (email_delivery_issue_id)');
    }
}
