<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240424122241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add author on file uploaded from signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE file f
        JOIN signalement s ON f.signalement_id = s.id
        JOIN user u ON u.email = s.mail_declarant
        SET f.uploaded_by_id = u.id
        WHERE f.uploaded_by_id IS NULL
        AND f.document_type IN (\'PHOTO_SITUATION\', \'SITUATION_FOYER_BAIL\', \'SITUATION_FOYER_ETAT_DES_LIEUX\', \'SITUATION_FOYER_DPE\', \'SITUATION_DIAGNOSTIC_PLOMB_AMIANTE\')');
    }

    public function down(Schema $schema): void
    {
    }
}
