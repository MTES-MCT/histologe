<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260212125909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table tiers_invitation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE tiers_invitation (
                id INT AUTO_INCREMENT NOT NULL,
                signalement_id INT NOT NULL,
                lastname VARCHAR(50) NOT NULL,
                firstname VARCHAR(50) NOT NULL,
                email VARCHAR(255) NOT NULL,
                telephone VARCHAR(20) DEFAULT NULL,
                token VARCHAR(64) NOT NULL,
                status VARCHAR(20) NOT NULL,
                created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX unique_tiers_invitation_token (token),
                UNIQUE INDEX uniq_tiers_invitation_signalement_status (signalement_id, status),
                INDEX idx_tiers_invitation_signalement (signalement_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");
        $this->addSql('ALTER TABLE tiers_invitation ADD CONSTRAINT FK_AB73BDFE65C5E57E FOREIGN KEY (signalement_id) REFERENCES signalement (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tiers_invitation DROP FOREIGN KEY FK_AB73BDFE65C5E57E');
        $this->addSql('DROP TABLE tiers_invitation');
    }
}
