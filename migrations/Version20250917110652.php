<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250917110652 extends AbstractMigration
{
    public function getDescription(): string
    {
        // https://data.geopf.fr/geocodage/search/?q=49530%20Or%C3%A9e%20d%27Anjou
        return 'Update Orée d\'Anjou with new code insee';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE commune SET code_insee = '49126' WHERE code_insee = '49069'
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE signalement SET insee_occupant = '49126' WHERE insee_occupant = '49069'
        SQL);
    }

    public function down(Schema $schema): void
    {
    }
}
