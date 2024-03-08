<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240304151459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'rename document types';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE file SET document_type = \''.DocumentType::PROCEDURE_RAPPORT_DE_VISITE->name.'\' WHERE document_type = \'VISITE\' AND file_type = \''.File::FILE_TYPE_DOCUMENT.'\'');
        $this->addSql('UPDATE file SET document_type = \''.DocumentType::PHOTO_VISITE->name.'\' WHERE document_type = \'VISITE\' AND file_type = \''.File::FILE_TYPE_PHOTO.'\'');
        $this->addSql('UPDATE file SET document_type = \''.DocumentType::PHOTO_SITUATION->name.'\' WHERE document_type = \'SITUATION\'');
    }

    public function down(Schema $schema): void
    {
    }
}
