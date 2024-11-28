<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241112145634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for storing failed emails for retries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE failed_email (
                id INT AUTO_INCREMENT NOT NULL,
                type VARCHAR(50) NOT NULL,
                to_email JSON NOT NULL,
                from_email VARCHAR(255) NOT NULL,
                from_fullname VARCHAR(255) NOT NULL,
                reply_to VARCHAR(255) NOT NULL,
                subject TEXT NOT NULL,
                context JSON NOT NULL,
                notify_usager TINYINT(1) DEFAULT 0 NOT NULL,
                error_message TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                is_resend_successful TINYINT(1) DEFAULT 0 NOT NULL,
                retry_count INT DEFAULT 0,
                last_attempt_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE failed_email');
    }
}
