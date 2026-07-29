<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728134137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ travaux_mise_en_conformite à l\'entité Signalement et création de la table signalement_procedure.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD travaux_mise_en_conformite VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE TABLE signalement_procedure (id INT AUTO_INCREMENT NOT NULL, signalement_id INT NOT NULL, procedure_type VARCHAR(255) NOT NULL, INDEX IDX_94EBAFAC65C5E57E (signalement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE signalement_procedure ADD CONSTRAINT FK_94EBAFAC65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_signalement_procedure_type ON signalement_procedure (signalement_id, procedure_type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP travaux_mise_en_conformite');
        $this->addSql('ALTER TABLE signalement_procedure DROP FOREIGN KEY FK_94EBAFAC65C5E57E');
        $this->addSql('DROP TABLE signalement_procedure');
        $this->addSql('DROP INDEX uniq_signalement_procedure_type ON signalement_procedure');
    }
}
