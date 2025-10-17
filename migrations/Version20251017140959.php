<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251017140959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove signalement_critere and signalement_situation tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_critere DROP FOREIGN KEY FK_81C2C8A79E5F45AB');
        $this->addSql('ALTER TABLE signalement_critere DROP FOREIGN KEY FK_81C2C8A765C5E57E');
        $this->addSql('ALTER TABLE signalement_situation DROP FOREIGN KEY FK_E4FA89793408E8AF');
        $this->addSql('ALTER TABLE signalement_situation DROP FOREIGN KEY FK_E4FA897965C5E57E');
        $this->addSql('DROP TABLE signalement_critere');
        $this->addSql('DROP TABLE signalement_situation');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE signalement_critere (signalement_id INT NOT NULL, critere_id INT NOT NULL, INDEX IDX_81C2C8A765C5E57E (signalement_id), INDEX IDX_81C2C8A79E5F45AB (critere_id), PRIMARY KEY(signalement_id, critere_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE signalement_situation (signalement_id INT NOT NULL, situation_id INT NOT NULL, INDEX IDX_E4FA89793408E8AF (situation_id), INDEX IDX_E4FA897965C5E57E (signalement_id), PRIMARY KEY(signalement_id, situation_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE signalement_critere ADD CONSTRAINT FK_81C2C8A79E5F45AB FOREIGN KEY (critere_id) REFERENCES critere (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE signalement_critere ADD CONSTRAINT FK_81C2C8A765C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE signalement_situation ADD CONSTRAINT FK_E4FA89793408E8AF FOREIGN KEY (situation_id) REFERENCES situation (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE signalement_situation ADD CONSTRAINT FK_E4FA897965C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
