<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230509154439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add column to partner to activate Esabora';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner ADD is_esabora_active TINYINT(1) DEFAULT NULL');
        $this->addSql('UPDATE partner SET is_esabora_active = 1 WHERE esabora_url IS NOT NULL');
        $this->addSql('UPDATE partner SET is_esabora_active = 0 WHERE esabora_url IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner DROP is_esabora_active');
    }
}
