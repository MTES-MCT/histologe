<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231013140346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new tables to manage désordres : desordre_categorie, desordre_critere, desordre_precision';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE desordre_categorie (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE desordre_critere (id INT AUTO_INCREMENT NOT NULL, desordre_categorie_id INT NOT NULL, slug_categorie VARCHAR(255) NOT NULL, label_categorie VARCHAR(255) NOT NULL, zone_categorie VARCHAR(255) NOT NULL, slug_critere VARCHAR(255) NOT NULL, label_critere VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6DCB3D10ECF01477 (desordre_categorie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE desordre_precision (id INT AUTO_INCREMENT NOT NULL, desordre_critere_id INT NOT NULL, coef INT NOT NULL, is_danger TINYINT(1) DEFAULT NULL, label VARCHAR(255) DEFAULT NULL, qualification JSON NOT NULL, desordre_precision_slug VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_23744FA01C3935AB (desordre_critere_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE desordre_critere ADD CONSTRAINT FK_6DCB3D10ECF01477 FOREIGN KEY (desordre_categorie_id) REFERENCES desordre_categorie (id)');
        $this->addSql('ALTER TABLE desordre_precision ADD CONSTRAINT FK_23744FA01C3935AB FOREIGN KEY (desordre_critere_id) REFERENCES desordre_critere (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE desordre_critere DROP FOREIGN KEY FK_6DCB3D10ECF01477');
        $this->addSql('ALTER TABLE desordre_precision DROP FOREIGN KEY FK_23744FA01C3935AB');
        $this->addSql('DROP TABLE desordre_categorie');
        $this->addSql('DROP TABLE desordre_critere');
        $this->addSql('DROP TABLE desordre_precision');
    }
}
