<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251002152518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add createdByPartner relation to Signalement entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD created_by_partner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B55114A0CAE01E FOREIGN KEY (created_by_partner_id) REFERENCES partner (id)');
        $this->addSql('CREATE INDEX IDX_F4B55114A0CAE01E ON signalement (created_by_partner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B55114A0CAE01E');
        $this->addSql('DROP INDEX IDX_F4B55114A0CAE01E ON signalement');
        $this->addSql('ALTER TABLE signalement DROP created_by_partner_id');
    }
}
