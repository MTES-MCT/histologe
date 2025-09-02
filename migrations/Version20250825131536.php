<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250825131536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'modify File entity to add territory relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD territory_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F0D3E6E0B3DCA FOREIGN KEY (territory_id) REFERENCES territory (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F0D3E6E0B3DCA');
        $this->addSql('ALTER TABLE file DROP territory_id');
    }
}
