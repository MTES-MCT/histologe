<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241203141732 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_partner table and insert data from user to user_partner';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_partner (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, partner_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6926201CA76ED395 (user_id), INDEX IDX_6926201C9393F8FE (partner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_partner ADD CONSTRAINT FK_6926201CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_partner ADD CONSTRAINT FK_6926201C9393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id)');
        // insert data from user to user_partner
        $this->addSql('INSERT INTO user_partner (user_id, partner_id, created_at) SELECT id, partner_id, NOW() FROM user WHERE partner_id IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_partner DROP FOREIGN KEY FK_6926201CA76ED395');
        $this->addSql('ALTER TABLE user_partner DROP FOREIGN KEY FK_6926201C9393F8FE');
        $this->addSql('DROP TABLE user_partner');
    }
}
