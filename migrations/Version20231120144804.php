<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231120144804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE desordre_categorie_signalement (desordre_categorie_id INT NOT NULL, signalement_id INT NOT NULL, INDEX IDX_E365880CECF01477 (desordre_categorie_id), INDEX IDX_E365880C65C5E57E (signalement_id), PRIMARY KEY(desordre_categorie_id, signalement_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE desordre_critere_signalement (desordre_critere_id INT NOT NULL, signalement_id INT NOT NULL, INDEX IDX_689D9BA81C3935AB (desordre_critere_id), INDEX IDX_689D9BA865C5E57E (signalement_id), PRIMARY KEY(desordre_critere_id, signalement_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE desordre_precision_signalement (desordre_precision_id INT NOT NULL, signalement_id INT NOT NULL, INDEX IDX_D390215F9FB07E9C (desordre_precision_id), INDEX IDX_D390215F65C5E57E (signalement_id), PRIMARY KEY(desordre_precision_id, signalement_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE desordre_categorie_signalement ADD CONSTRAINT FK_E365880CECF01477 FOREIGN KEY (desordre_categorie_id) REFERENCES desordre_categorie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE desordre_categorie_signalement ADD CONSTRAINT FK_E365880C65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE desordre_critere_signalement ADD CONSTRAINT FK_689D9BA81C3935AB FOREIGN KEY (desordre_critere_id) REFERENCES desordre_critere (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE desordre_critere_signalement ADD CONSTRAINT FK_689D9BA865C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE desordre_precision_signalement ADD CONSTRAINT FK_D390215F9FB07E9C FOREIGN KEY (desordre_precision_id) REFERENCES desordre_precision (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE desordre_precision_signalement ADD CONSTRAINT FK_D390215F65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE desordre_categorie CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE desordre_critere CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE desordre_precision CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE desordre_categorie_signalement DROP FOREIGN KEY FK_E365880CECF01477');
        $this->addSql('ALTER TABLE desordre_categorie_signalement DROP FOREIGN KEY FK_E365880C65C5E57E');
        $this->addSql('ALTER TABLE desordre_critere_signalement DROP FOREIGN KEY FK_689D9BA81C3935AB');
        $this->addSql('ALTER TABLE desordre_critere_signalement DROP FOREIGN KEY FK_689D9BA865C5E57E');
        $this->addSql('ALTER TABLE desordre_precision_signalement DROP FOREIGN KEY FK_D390215F9FB07E9C');
        $this->addSql('ALTER TABLE desordre_precision_signalement DROP FOREIGN KEY FK_D390215F65C5E57E');
        $this->addSql('DROP TABLE desordre_categorie_signalement');
        $this->addSql('DROP TABLE desordre_critere_signalement');
        $this->addSql('DROP TABLE desordre_precision_signalement');
        $this->addSql('ALTER TABLE desordre_categorie CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE desordre_critere CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE desordre_precision CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
