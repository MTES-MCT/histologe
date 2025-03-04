<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250303140936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new columns to failed_email table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM failed_email WHERE retry_count > 900 AND is_resend_successful = 0');
        $this->addSql('ALTER TABLE failed_email ADD is_recipient_visible TINYINT(1) NOT NULL');
        $this->addSql('UPDATE failed_email SET is_recipient_visible = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE failed_email DROP is_recipient_visible');
    }
}
