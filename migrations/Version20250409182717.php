<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250409182717 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pro_connect_uid to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD pro_connect_user_id VARCHAR(255) DEFAULT NULL AFTER password');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64961F0BB97 ON user (pro_connect_user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D64961F0BB97 ON user');
        $this->addSql('ALTER TABLE user DROP pro_connect_user_id');
    }
}
