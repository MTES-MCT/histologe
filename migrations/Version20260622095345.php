<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622095345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SuiviDelayed entity and replace DOCUMENT_DELETED_BY_USAGER suivi by SIGNALEMENT_EDITED_FO for existing suivis';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE suivi_delayed (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, signalement_id INT NOT NULL, suivi_category VARCHAR(255) NOT NULL, suivi_delayed_type VARCHAR(255) NOT NULL, changes JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E946BE92A76ED395 (user_id), INDEX IDX_E946BE9265C5E57E (signalement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE suivi_delayed ADD CONSTRAINT FK_E946BE92A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE suivi_delayed ADD CONSTRAINT FK_E946BE9265C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)');

        $this->addSql('ALTER TABLE file ADD suivi_delayed_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F36107D2FCED4 FOREIGN KEY (suivi_delayed_id) REFERENCES suivi_delayed (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8C9F36107D2FCED4 ON file (suivi_delayed_id)');

        $this->addSql('UPDATE suivi SET category = "SIGNALEMENT_EDITED_FO" WHERE category = "DOCUMENT_DELETED_BY_USAGER"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi_delayed DROP FOREIGN KEY FK_E946BE92A76ED395');
        $this->addSql('ALTER TABLE suivi_delayed DROP FOREIGN KEY FK_E946BE9265C5E57E');
        $this->addSql('DROP TABLE suivi_delayed');

        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F36107D2FCED4');
        $this->addSql('DROP INDEX IDX_8C9F36107D2FCED4 ON file');
        $this->addSql('ALTER TABLE file DROP suivi_delayed_id');
    }
}
