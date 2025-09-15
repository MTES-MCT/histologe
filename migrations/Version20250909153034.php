<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250909153034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uuid column to partner table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner ADD uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'AFTER id');
        $this->addSql('UPDATE partner SET uuid = UUID()');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_312B3E16D17F50A6 ON partner (uuid)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649D17F50A6 ON user (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_312B3E16D17F50A6 ON partner');
        $this->addSql('ALTER TABLE partner DROP uuid');
        $this->addSql('DROP INDEX UNIQ_8D93D649D17F50A6 ON user');
    }
}
