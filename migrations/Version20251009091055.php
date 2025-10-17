<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251009091055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add waiting_notification field to suivi entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi ADD waiting_notification TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi DROP waiting_notification');
    }
}
