<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230515174855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add intervention table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE intervention (id INT AUTO_INCREMENT NOT NULL, signalement_id INT NOT NULL, partner_id INT DEFAULT NULL, scheduled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', registered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, documents JSON NOT NULL, details LONGTEXT DEFAULT NULL, conclude_procedure TINYTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', reminder_before_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', reminder_conclusion_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', occupant_present TINYINT(1) DEFAULT NULL, done_by VARCHAR(255) DEFAULT NULL, INDEX IDX_D11814AB65C5E57E (signalement_id), INDEX IDX_D11814AB9393F8FE (partner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE intervention ADD provider_name VARCHAR(255) DEFAULT NULL COMMENT \'Provider name have created the intervention\', ADD provider_id VARCHAR(255) DEFAULT NULL COMMENT \'Unique id used by the provider\'');
        $this->addSql('ALTER TABLE intervention CHANGE provider_id provider_id INT DEFAULT NULL COMMENT \'Unique id used by the provider\'');
        $this->addSql('ALTER TABLE intervention ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB9393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB65C5E57E');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB9393F8FE');
        $this->addSql('DROP TABLE intervention');
    }
}
