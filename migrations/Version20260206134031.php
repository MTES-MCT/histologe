<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206134031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete FailedEmail entries with specific error messages and email addresses containing .archived@ to clean up the database from known issues related to email delivery.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DELETE FROM failed_email
            WHERE error_message IN (
                'Unable to send an email: email is not valid in to (code 400).',
                'Unable to send an email: email is not valid in bcc (code 400).',
                'An email must have a "To", "Cc", or "Bcc" header.'
            )
            AND CAST(to_email AS CHAR) REGEXP '\\.archived@'
        SQL);
    }

    public function down(Schema $schema): void
    {
    }
}
