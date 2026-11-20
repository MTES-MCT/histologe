<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261119123627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppression et mise à jour de communes incohérentes';
    }

    public function up(Schema $schema): void
    {
        // Marseille 4e Arrondissement 13005 13204
        $this->addSql("DELETE FROM commune WHERE code_postal = '13005' AND code_insee = '13204'");
        // Marseille 6e Arrondissement 13007 13206
        $this->addSql("DELETE FROM commune WHERE code_postal = '13007' AND code_insee = '13206'");
        // Marseille 10e Arrondissement 13011 13210
        $this->addSql("DELETE FROM commune WHERE code_postal = '13011' AND code_insee = '13210'");
        // Marseille 11e Arrondissement 13010 13211
        $this->addSql("DELETE FROM commune WHERE code_postal = '13010' AND code_insee = '13211'");
        // Marseille 11e Arrondissement 13012 13211
        $this->addSql("DELETE FROM commune WHERE code_postal = '13012' AND code_insee = '13211'");
        // Marseille 12e Arrondissement 13013 13212
        $this->addSql("DELETE FROM commune WHERE code_postal = '13013' AND code_insee = '13212'");
        // Marseille 12e Arrondissement 13004 13212
        $this->addSql("DELETE FROM commune WHERE code_postal = '13004' AND code_insee = '13212'");
        // Marseille 13e Arrondissement 13012 13213
        $this->addSql("DELETE FROM commune WHERE code_postal = '13012' AND code_insee = '13213'");
        // Marseille 14e Arrondissement 13013 13214
        $this->addSql("DELETE FROM commune WHERE code_postal = '13013' AND code_insee = '13214'");
        // Saint-Pierre-d'Entremont 73670 38446
        $this->addSql("UPDATE commune SET code_postal = '38380' WHERE code_postal = '73670' AND code_insee = '38446'");
        // Thièvres 62760 80756
        $this->addSql("UPDATE commune SET code_postal = '80560' WHERE code_postal = '62760' AND code_insee = '80756'");
    }

    public function down(Schema $schema): void
    {
    }
}
