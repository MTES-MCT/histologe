<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231222094349 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Visite competences according to partner type';
    }

    public function addCompetence(string $competenceName, string $typeName)
    {
        $this->addSql('UPDATE partner SET competence = CONCAT(competence, \',\', \''.$competenceName.'\') WHERE competence IS NOT NULL AND competence NOT LIKE \'%'.$competenceName.'%\' AND type = \''.$typeName.'\'');
        $this->addSql('UPDATE partner SET competence = \''.$competenceName.'\' WHERE competence IS NULL AND type = \''.$typeName.'\'');
    }

    public function up(Schema $schema): void
    {
        $this->addCompetence(Qualification::VISITES->name, PartnerType::OPERATEUR_VISITES_ET_TRAVAUX->name);
        $this->addCompetence(Qualification::VISITES->name, PartnerType::ARS->name);
        $this->addCompetence(Qualification::VISITES->name, PartnerType::CAF_MSA->name);
        $this->addCompetence(Qualification::VISITES->name, PartnerType::COMMUNE_SCHS->name);
        $this->addCompetence(Qualification::VISITES->name, PartnerType::DISPOSITIF_RENOVATION_HABITAT->name);
    }

    public function down(Schema $schema): void
    {
    }
}
