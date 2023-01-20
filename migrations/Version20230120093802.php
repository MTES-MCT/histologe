<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230120093802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create new table to link Signalement with declarant and occupant as users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE signalement_usager (id INT AUTO_INCREMENT NOT NULL, signalement_id INT NOT NULL, declarant_id INT DEFAULT NULL, occupant_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_408FE76765C5E57E (signalement_id), INDEX IDX_408FE767EC439BC (declarant_id), INDEX IDX_408FE76767BAA0E5 (occupant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE signalement_usager ADD CONSTRAINT FK_408FE76765C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)');
        $this->addSql('ALTER TABLE signalement_usager ADD CONSTRAINT FK_408FE767EC439BC FOREIGN KEY (declarant_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE signalement_usager ADD CONSTRAINT FK_408FE76767BAA0E5 FOREIGN KEY (occupant_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_usager DROP FOREIGN KEY FK_408FE76765C5E57E');
        $this->addSql('ALTER TABLE signalement_usager DROP FOREIGN KEY FK_408FE767EC439BC');
        $this->addSql('ALTER TABLE signalement_usager DROP FOREIGN KEY FK_408FE76767BAA0E5');
        $this->addSql('DROP TABLE signalement_usager');
    }
}
