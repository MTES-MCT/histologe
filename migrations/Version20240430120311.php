<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240430120311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update file document_type from AUTRE to AUTRE_PROCEDURE for documents uploaded by users that are not USAGER';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE file f SET f.document_type = "AUTRE_PROCEDURE" WHERE f.file_type = "document" AND f.document_type = "AUTRE" AND f.uploaded_by_id IS NOT NULL AND f.uploaded_by_id NOT IN (SELECT id FROM user WHERE roles LIKE "%ROLE_USAGER%")');
    }

    public function down(Schema $schema): void
    {
    }
}
