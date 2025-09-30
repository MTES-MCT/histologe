<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250930081148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove desordre_categorie_signalement and desordre_critere_signalement tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE desordre_categorie_signalement DROP FOREIGN KEY FK_E365880CECF01477');
        $this->addSql('ALTER TABLE desordre_categorie_signalement DROP FOREIGN KEY FK_E365880C65C5E57E');
        $this->addSql('ALTER TABLE desordre_critere_signalement DROP FOREIGN KEY FK_689D9BA865C5E57E');
        $this->addSql('ALTER TABLE desordre_critere_signalement DROP FOREIGN KEY FK_689D9BA81C3935AB');
        $this->addSql('DROP TABLE desordre_categorie_signalement');
        $this->addSql('DROP TABLE desordre_critere_signalement');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE desordre_categorie_signalement (signalement_id INT NOT NULL, desordre_categorie_id INT NOT NULL, INDEX IDX_E365880C65C5E57E (signalement_id), INDEX IDX_E365880CECF01477 (desordre_categorie_id), PRIMARY KEY(signalement_id, desordre_categorie_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE desordre_critere_signalement (signalement_id INT NOT NULL, desordre_critere_id INT NOT NULL, INDEX IDX_689D9BA81C3935AB (desordre_critere_id), INDEX IDX_689D9BA865C5E57E (signalement_id), PRIMARY KEY(signalement_id, desordre_critere_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE desordre_categorie_signalement ADD CONSTRAINT FK_E365880CECF01477 FOREIGN KEY (desordre_categorie_id) REFERENCES desordre_categorie (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE desordre_categorie_signalement ADD CONSTRAINT FK_E365880C65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE desordre_critere_signalement ADD CONSTRAINT FK_689D9BA865C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE desordre_critere_signalement ADD CONSTRAINT FK_689D9BA81C3935AB FOREIGN KEY (desordre_critere_id) REFERENCES desordre_critere (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
