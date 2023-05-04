<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230428083613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add of default competences according to partner type';
    }

    public function addCompetence(string $competenceName, string $typeName)
    {
        $this->addSql('UPDATE partner SET competence = CONCAT(competence, \',\', \''.$competenceName.'\') WHERE competence IS NOT NULL AND type = \''.$typeName.'\'');
        $this->addSql('UPDATE partner SET competence = \''.$competenceName.'\' WHERE competence IS NULL AND type = \''.$typeName.'\'');
    }

    public function addCompetenceHorsExpe(string $competenceName, string $typeName)
    {
        $this->addSql('UPDATE partner INNER JOIN territory ON partner.territory_id = territory.id SET competence = CONCAT(competence, \',\', \''.$competenceName.'\') WHERE competence IS NOT NULL AND type = \''.$typeName.'\' AND territory.zip NOT IN (\'63\', \'89\')');
        $this->addSql('UPDATE partner INNER JOIN territory ON partner.territory_id = territory.id SET competence = \''.$competenceName.'\' WHERE competence IS NULL AND type = \''.$typeName.'\' AND territory.zip NOT IN (\'63\', \'89\')');
    }

    public function up(Schema $schema): void
    {
        $this->addCompetence(Qualification::ACCOMPAGNEMENT_JURIDIQUE->name, PartnerType::ADIL->name);
        $this->addCompetence(Qualification::ACCOMPAGNEMENT_SOCIAL->name, PartnerType::CCAS->name);
        $this->addCompetence(Qualification::ACCOMPAGNEMENT_SOCIAL->name, PartnerType::CONSEIL_DEPARTEMENTAL->name);
        $this->addCompetence(Qualification::ACCOMPAGNEMENT_TRAVAUX->name, PartnerType::CONSEIL_DEPARTEMENTAL->name);
        $this->addCompetence(Qualification::ACCOMPAGNEMENT_TRAVAUX->name, PartnerType::DISPOSITIF_RENOVATION_HABITAT->name);
        $this->addCompetence(Qualification::ACCOMPAGNEMENT_TRAVAUX->name, PartnerType::OPERATEUR_VISITES_ET_TRAVAUX->name);
        $this->addCompetence(Qualification::ARRETES->name, PartnerType::ARS->name);
        $this->addCompetence(Qualification::ARRETES->name, PartnerType::COMMUNE_SCHS->name);
        $this->addCompetence(Qualification::ARRETES->name, PartnerType::DDT_M->name);
        $this->addCompetence(Qualification::CONCILIATION->name, PartnerType::ADIL->name);
        $this->addCompetence(Qualification::CONCILIATION->name, PartnerType::CCAS->name);
        $this->addCompetence(Qualification::CONCILIATION->name, PartnerType::COMMUNE_SCHS->name);
        $this->addCompetence(Qualification::CONCILIATION->name, PartnerType::CONCILIATEURS->name);
        $this->addCompetence(Qualification::CONCILIATION->name, PartnerType::DDT_M->name);
        $this->addCompetence(Qualification::CONCILIATION->name, PartnerType::DISPOSITIF_RENOVATION_HABITAT->name);
        $this->addCompetence(Qualification::CONCILIATION->name, PartnerType::EPCI->name);
        $this->addCompetence(Qualification::CONCILIATION->name, PartnerType::OPERATEUR_VISITES_ET_TRAVAUX->name);
        $this->addCompetence(Qualification::CONSIGNATION_AL->name, PartnerType::CAF_MSA->name);
        $this->addCompetence(Qualification::DALO->name, PartnerType::DDETS->name);
        $this->addCompetence(Qualification::DALO->name, PartnerType::DDT_M->name);
        $this->addCompetence(Qualification::DALO->name, PartnerType::PREFECTURE->name);
        $this->addCompetence(Qualification::DIOGENE->name, PartnerType::ARS->name);
        $this->addCompetence(Qualification::DIOGENE->name, PartnerType::BAILLEUR_SOCIAL->name);
        $this->addCompetence(Qualification::DIOGENE->name, PartnerType::CCAS->name);
        $this->addCompetence(Qualification::DIOGENE->name, PartnerType::COMMUNE_SCHS->name);
        $this->addCompetence(Qualification::FSL->name, PartnerType::CONSEIL_DEPARTEMENTAL->name);
        $this->addCompetence(Qualification::HEBERGEMENT_RELOGEMENT->name, PartnerType::BAILLEUR_SOCIAL->name);
        $this->addCompetence(Qualification::HEBERGEMENT_RELOGEMENT->name, PartnerType::DDETS->name);
        $this->addCompetence(Qualification::HEBERGEMENT_RELOGEMENT->name, PartnerType::DDT_M->name);
        $this->addCompetence(Qualification::INSALUBRITE->name, PartnerType::ARS->name);
        $this->addCompetence(Qualification::INSALUBRITE->name, PartnerType::COMMUNE_SCHS->name);
        $this->addCompetence(Qualification::MISE_EN_SECURITE_PERIL->name, PartnerType::BAILLEUR_SOCIAL->name);
        $this->addCompetence(Qualification::MISE_EN_SECURITE_PERIL->name, PartnerType::COMMUNE_SCHS->name);
        $this->addCompetence(Qualification::NON_DECENCE->name, PartnerType::BAILLEUR_SOCIAL->name);
        $this->addCompetence(Qualification::NON_DECENCE->name, PartnerType::CAF_MSA->name);
        $this->addCompetence(Qualification::NUISIBLES->name, PartnerType::BAILLEUR_SOCIAL->name);
        $this->addCompetence(Qualification::NUISIBLES->name, PartnerType::COMMUNE_SCHS->name);
        $this->addCompetence(Qualification::RSD->name, PartnerType::BAILLEUR_SOCIAL->name);
        $this->addCompetence(Qualification::RSD->name, PartnerType::COMMUNE_SCHS->name);
        $this->addCompetence(Qualification::VISITES->name, PartnerType::ARS->name);
        $this->addCompetence(Qualification::VISITES->name, PartnerType::CAF_MSA->name);
        $this->addCompetence(Qualification::VISITES->name, PartnerType::COMMUNE_SCHS->name);
        $this->addCompetence(Qualification::VISITES->name, PartnerType::DISPOSITIF_RENOVATION_HABITAT->name);
        $this->addCompetenceHorsExpe(Qualification::NON_DECENCE_ENERGETIQUE->name, PartnerType::ADIL->name);
        $this->addCompetenceHorsExpe(Qualification::NON_DECENCE_ENERGETIQUE->name, PartnerType::BAILLEUR_SOCIAL->name);
        $this->addCompetenceHorsExpe(Qualification::NON_DECENCE_ENERGETIQUE->name, PartnerType::CAF_MSA->name);
        $this->addCompetenceHorsExpe(Qualification::NON_DECENCE_ENERGETIQUE->name, PartnerType::COMMUNE_SCHS->name);
        $this->addCompetenceHorsExpe(Qualification::NON_DECENCE_ENERGETIQUE->name, PartnerType::DISPOSITIF_RENOVATION_HABITAT->name);
        $this->addCompetenceHorsExpe(Qualification::NON_DECENCE_ENERGETIQUE->name, PartnerType::OPERATEUR_VISITES_ET_TRAVAUX->name);
    }

    public function down(Schema $schema): void
    {
    }
}
