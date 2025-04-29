<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250428102700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email_notifiable column to partner table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE partner ADD email_notifiable TINYINT(1) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE partner SET email_notifiable = 1 WHERE email IS NOT NULL AND email != ''
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE partner DROP email_notifiable
        SQL);
    }
}
