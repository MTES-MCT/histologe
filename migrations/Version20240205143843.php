<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\DocumentType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240205143843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add description column to file';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD description LONGTEXT DEFAULT NULL');
        $this->addSql('UPDATE file SET document_type = \''.DocumentType::AUTRE->name.'\' WHERE document_type IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP description');
    }
}
