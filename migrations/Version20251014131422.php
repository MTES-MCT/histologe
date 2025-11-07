<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251014131422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new column to track when intervention conclusion is edited';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention ADD conclusion_visite_edited_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE comment_before_visite comment_before_visite LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE intervention ADD notify_usager TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention DROP conclusion_visite_edited_at, CHANGE comment_before_visite comment_before_visite TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE intervention DROP notify_usager');
    }
}
