<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\File;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240329143924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set file_type document for document_type SITUATION_FOYER_BAIL, SITUATION_FOYER_ETAT_DES_LIEUX or SITUATION_FOYER_DPE ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE file SET file_type = \''.File::FILE_TYPE_DOCUMENT
            .'\' WHERE document_type = \'SITUATION_FOYER_BAIL\' AND file_type = \''.File::FILE_TYPE_PHOTO.'\'');
        $this->addSql('UPDATE file SET file_type = \''.File::FILE_TYPE_DOCUMENT
            .'\' WHERE document_type = \'SITUATION_FOYER_ETAT_DES_LIEUX\' AND file_type = \''.File::FILE_TYPE_PHOTO.'\'');
        $this->addSql('UPDATE file SET file_type = \''.File::FILE_TYPE_DOCUMENT
            .'\' WHERE document_type = \'SITUATION_FOYER_DPE\' AND file_type = \''.File::FILE_TYPE_PHOTO.'\'');
    }

    public function down(Schema $schema): void
    {
    }
}
