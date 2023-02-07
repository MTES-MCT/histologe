<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230207063027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Authorize the signalement for the La Communauté d\'Agglomération de l\'Ouest Rhodanien ';
    }

    public function up(Schema $schema): void
    {
        $codesInsee = [69001, 69006, 69008, 69037, 69054, 69060, 69066, 69070, 69075, 69093, 69102, 69107, 69174, 69130,
            69160, 69164, 69169, 69181, 69183, 69188, 69200, 69214, 69217, 69225, 69229, 69234, 69240, 69243, 69248,
            69254, 69157, ];

        $this->addSql(
            'UPDATE territory SET authorized_codes_insee = \''.json_encode($codesInsee).'\' WHERE zip = \'69\''
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
