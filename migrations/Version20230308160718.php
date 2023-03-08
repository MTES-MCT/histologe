<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230308160718 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE signalement_analytics (id INT AUTO_INCREMENT NOT NULL, last_suivi_user_by_id INT DEFAULT NULL, signalement_id INT NOT NULL, last_suivi_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E215F53BC475BC14 (last_suivi_user_by_id), UNIQUE INDEX UNIQ_E215F53B65C5E57E (signalement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE signalement_analytics ADD CONSTRAINT FK_E215F53BC475BC14 FOREIGN KEY (last_suivi_user_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE signalement_analytics ADD CONSTRAINT FK_E215F53B65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE signalement_analytics DROP FOREIGN KEY FK_E215F53BC475BC14');
        $this->addSql('ALTER TABLE signalement_analytics DROP FOREIGN KEY FK_E215F53B65C5E57E');
        $this->addSql('DROP TABLE signalement_analytics');
    }
}
