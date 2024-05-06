<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\DocumentType;
use App\Entity\Signalement;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240503102522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update document type based on title)';
    }

    public function up(Schema $schema): void
    {
        $clauseisAutre = "AND f.document_type = '".DocumentType::AUTRE->name."'";
        $clauseIsAutreProcedure = "AND f.document_type = '".DocumentType::AUTRE_PROCEDURE->name."'";
        $clauseisAutreOrIsAutreProcedure = "AND (f.document_type = '".DocumentType::AUTRE->name."' OR f.document_type = '".DocumentType::AUTRE_PROCEDURE->name."')";

        $autreTitle = ['photo'];
        $this->addSql($this->generateUpdateSQL(DocumentType::AUTRE->name, $autreTitle, $clauseIsAutreProcedure));

        $procedureRapportDeVisite = ['visite'];
        $this->addSql($this->generateUpdateSQL(DocumentType::PROCEDURE_RAPPORT_DE_VISITE->name, $procedureRapportDeVisite, $clauseisAutreOrIsAutreProcedure));

        $situationFoyerBail = ['bail_', 'bail-', 'bail '];
        $this->addSql($this->generateUpdateSQL(DocumentType::SITUATION_FOYER_BAIL->name, $situationFoyerBail, $clauseisAutreOrIsAutreProcedure));

        $situationFoyerEtatDesLieux = ['lieux'];
        $this->addSql($this->generateUpdateSQL(DocumentType::SITUATION_FOYER_ETAT_DES_LIEUX->name, $situationFoyerEtatDesLieux, $clauseisAutreOrIsAutreProcedure));

        $situationFoyerDPE = ['DPE'];
        $this->addSql($this->generateUpdateSQL(DocumentType::SITUATION_FOYER_DPE->name, $situationFoyerDPE, $clauseisAutreOrIsAutreProcedure));

        $autreProcedure = [
            'rapport', 'courrier', 'demeure', 'facture', 'devis', 'travaux', 'arrete', 'urgence',
            'peril', 'securite', 'bailleur',
            'LMD', 'mairie', 'lettre', 'proprio', 'proprietaire',
        ];
        $this->addSql($this->generateUpdateSQL(DocumentType::AUTRE_PROCEDURE->name, $autreProcedure, $clauseisAutre));
    }

    public function generateUpdateSQL($type, $terms, $clauseSup)
    {
        $likeClauses = [];
        foreach ($terms as $term) {
            $likeClauses[] = "f.title LIKE '%".$term."%'";
        }
        $likeClause = 'AND ('.implode(' OR ', $likeClauses).') ';

        $sql = "
        UPDATE file f
        INNER JOIN signalement s ON f.signalement_id = s.id
        SET f.document_type = '".$type."'
        WHERE f.file_type = 'document'
        AND (s.statut = '".Signalement::STATUS_NEED_VALIDATION."' OR s.statut = '".Signalement::STATUS_ACTIVE."') "
        .$likeClause.$clauseSup;

        return $sql;
    }

    public function down(Schema $schema): void
    {
    }
}
