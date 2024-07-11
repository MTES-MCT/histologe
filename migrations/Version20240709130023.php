<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240709130023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add raison sociale and siret to bailleur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bailleur ADD raison_sociale VARCHAR(255) DEFAULT NULL, ADD siret VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7ABB27F3E2B45978 ON bailleur (raison_sociale)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7ABB27F326E94372 ON bailleur (siret)');
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B5511457B5D0A2');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B5511457B5D0A2 FOREIGN KEY (bailleur_id) REFERENCES bailleur (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_7ABB27F3E2B45978 ON bailleur');
        $this->addSql('DROP INDEX UNIQ_7ABB27F326E94372 ON bailleur');
        $this->addSql('ALTER TABLE bailleur DROP raison_sociale, DROP siret');
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B5511457B5D0A2');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B5511457B5D0A2 FOREIGN KEY (bailleur_id) REFERENCES bailleur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
