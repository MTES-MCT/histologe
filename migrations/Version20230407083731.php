<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230407083731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add Intervention table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE intervention (id INT AUTO_INCREMENT NOT NULL, signalement_id INT NOT NULL, partner_id INT DEFAULT NULL, date DATETIME DEFAULT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, documents JSON NOT NULL, details LONGTEXT DEFAULT NULL, reminder_before_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', reminder_conclusion_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D11814AB65C5E57E (signalement_id), INDEX IDX_D11814AB9393F8FE (partner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
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
