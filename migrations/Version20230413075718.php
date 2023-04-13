<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230413075718 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix competence VISITE';
    }

    public function up(Schema $schema): void
    {
        $competenceVisite = [Qualification::VISITES];
        $this->addSql('UPDATE partner SET competence = NULL WHERE competence = \''.json_encode($competenceVisite).'\'');

        $this->addSql('UPDATE partner SET competence = CONCAT(competence, \',\', \''.Qualification::VISITES->name.'\') WHERE competence IS NOT NULL AND type = \''.PartnerType::OPERATEUR_VISITES_ET_TRAVAUX->name.'\'');
        $this->addSql('UPDATE partner SET competence = \''.Qualification::VISITES->name.'\' WHERE competence IS NULL AND type = \''.PartnerType::OPERATEUR_VISITES_ET_TRAVAUX->name.'\'');
    }

    public function down(Schema $schema): void
    {
    }
}
