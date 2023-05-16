<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230411201414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete is_commune from partner';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner DROP is_commune');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner ADD is_commune TINYINT(1) DEFAULT NULL');
    }
}
