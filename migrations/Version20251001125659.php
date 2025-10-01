<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251001125659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add partner_id to signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD partner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B551149393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id)');
        $this->addSql('CREATE INDEX IDX_F4B551149393F8FE ON signalement (partner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B551149393F8FE');
        $this->addSql('DROP INDEX IDX_F4B551149393F8FE ON signalement');
        $this->addSql('ALTER TABLE signalement DROP partner_id');
    }
}
