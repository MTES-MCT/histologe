<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230224152702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE signalement_qualification (id INT AUTO_INCREMENT NOT NULL, signalement_id INT NOT NULL, qualification VARCHAR(255) NOT NULL, desordres JSON DEFAULT NULL, date_dernier_bail DATE DEFAULT NULL, details JSON DEFAULT NULL, INDEX IDX_6617D77965C5E57E (signalement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE signalement_qualification ADD CONSTRAINT FK_6617D77965C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE signalement_qualification DROP FOREIGN KEY FK_6617D77965C5E57E');
        $this->addSql('DROP TABLE signalement_qualification');
    }
}
