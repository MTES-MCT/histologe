<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231213171924 extends AbstractMigration
{
    private const ROLE_SUPER_AMIN = '%ROLE_ADMIN%';

    public function getDescription(): string
    {
        return 'remove user relation for files uploaded by a user from a different territory';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
        UPDATE file
        SET uploaded_by_id = NULL
        WHERE uploaded_by_id IN (
            SELECT uploaded_by_id FROM (
                SELECT f.uploaded_by_id
                FROM file f
                INNER JOIN signalement s ON s.id = f.signalement_id
                INNER JOIN user u ON u.id = f.uploaded_by_id
                WHERE u.territory_id != s.territory_id
                AND u.roles NOT LIKE :role_super_admin
            ) AS subquery
        )', ['role_super_admin' => self::ROLE_SUPER_AMIN]);
    }

    public function down(Schema $schema): void
    {
    }
}
