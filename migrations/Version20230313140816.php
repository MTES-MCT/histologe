<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230313140816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update field motif_cloture of signalement and affectation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE signalement SET motif_cloture = "RSD" WHERE motif_cloture="INFRACTION RSD"');
        $this->addSql('UPDATE signalement SET motif_cloture = "LOGEMENT_DECENT" WHERE motif_cloture="LOGEMENT DECENT"');
        $this->addSql('UPDATE signalement SET motif_cloture = "DEPART_OCCUPANT" WHERE motif_cloture="LOCATAIRE PARTI"');
        $this->addSql('UPDATE signalement SET motif_cloture = "LOGEMENT_VENDU" WHERE motif_cloture="LOGEMENT VENDU"');
        $this->addSql('UPDATE signalement SET motif_cloture = "TRAVAUX_FAITS_OU_EN_COURS" WHERE motif_cloture="RESOLU"');
        $this->addSql('UPDATE affectation SET motif_cloture = "RSD" WHERE motif_cloture="INFRACTION RSD"');
        $this->addSql('UPDATE affectation SET motif_cloture = "LOGEMENT_DECENT" WHERE motif_cloture="LOGEMENT DECENT"');
        $this->addSql('UPDATE affectation SET motif_cloture = "DEPART_OCCUPANT" WHERE motif_cloture="LOCATAIRE PARTI"');
        $this->addSql('UPDATE affectation SET motif_cloture = "LOGEMENT_VENDU" WHERE motif_cloture="LOGEMENT VENDU"');
        $this->addSql('UPDATE affectation SET motif_cloture = "TRAVAUX_FAITS_OU_EN_COURS" WHERE motif_cloture="RESOLU"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE signalement SET motif_cloture = "INFRACTION RSD" WHERE motif_cloture="RSD"');
        $this->addSql('UPDATE signalement SET motif_cloture = "LOGEMENT DECENT" WHERE motif_cloture="LOGEMENT_DECENT"');
        $this->addSql('UPDATE signalement SET motif_cloture = "LOCATAIRE PARTI" WHERE motif_cloture="DEPART_OCCUPANT"');
        $this->addSql('UPDATE signalement SET motif_cloture = "LOGEMENT VENDU" WHERE motif_cloture="LOGEMENT_VENDU"');
        $this->addSql('UPDATE signalement SET motif_cloture = "RESOLU" WHERE motif_cloture="TRAVAUX_FAITS_OU_EN_COURS"');
        $this->addSql('UPDATE affectation SET motif_cloture = "INFRACTION RSD" WHERE motif_cloture="RSD"');
        $this->addSql('UPDATE affectation SET motif_cloture = "LOGEMENT DECENT" WHERE motif_cloture="LOGEMENT_DECENT"');
        $this->addSql('UPDATE affectation SET motif_cloture = "LOCATAIRE PARTI" WHERE motif_cloture="DEPART_OCCUPANT"');
        $this->addSql('UPDATE affectation SET motif_cloture = "LOGEMENT VENDU" WHERE motif_cloture="LOGEMENT_VENDU"');
        $this->addSql('UPDATE affectation SET motif_cloture = "RESOLU" WHERE motif_cloture="TRAVAUX_FAITS_OU_EN_COURS"');
    }
}
