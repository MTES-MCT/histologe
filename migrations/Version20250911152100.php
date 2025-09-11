<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\DocumentType;
use App\Entity\Enum\Qualification;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250911152100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'complete partner_competence, description and documentType for 6 nde files';
    }

    public function up(Schema $schema): void
    {
        $this->connection->update('file', [
            'partner_competence' => Qualification::NON_DECENCE_ENERGETIQUE->value,
            'description' => 'Texte bidon pour DPE',
            'document_type' => DocumentType::MODELE_DE_COURRIER->value,
            'is_standalone' => 1,
        ], ['filename' => '1_Demande_de_transmission_d_une_copie_d_un_DPE.docx']);

        $this->connection->update('file', [
            'partner_competence' => Qualification::NON_DECENCE_ENERGETIQUE->value,
            'description' => 'Texte bidon pour mise en conformité',
            'document_type' => DocumentType::MODELE_DE_COURRIER->value,
            'is_standalone' => 1,
        ], ['filename' => '2_Information_au_bailleur_Mise_en_conformite.docx']);

        $this->connection->update('file', [
            'partner_competence' => Qualification::NON_DECENCE_ENERGETIQUE->value,
            'description' => 'Texte bidon pour mise en demeure',
            'document_type' => DocumentType::MODELE_DE_COURRIER->value,
            'is_standalone' => 1,
        ], ['filename' => '3_Mise_en_demeure.docx']);

        $this->connection->update('file', [
            'partner_competence' => Qualification::NON_DECENCE_ENERGETIQUE->value,
            'description' => 'Texte bidon pour ADIL',
            'document_type' => DocumentType::MODELE_DE_COURRIER->value,
            'is_standalone' => 1,
        ], ['filename' => '4_Invitation_a_contacter_l_ADIL.docx']);

        $this->connection->update('file', [
            'partner_competence' => Qualification::NON_DECENCE_ENERGETIQUE->value,
            'description' => 'Texte bidon pour engagement du bailleur',
            'document_type' => DocumentType::MODELE_DE_COURRIER->value,
            'is_standalone' => 1,
        ], ['filename' => '5_Engagement_du_bailleur_a_realiser_des_travaux.docx']);

        $this->connection->update('file', [
            'partner_competence' => Qualification::NON_DECENCE_ENERGETIQUE->value,
            'description' => 'Texte bidon pour commission départementale',
            'document_type' => DocumentType::PROCEDURE->value,
            'is_standalone' => 1,
        ], ['filename' => '6_Saisine_de_la_Commission_departementale_de_conciliation.docx']);
    }

    public function down(Schema $schema): void
    {
        foreach ([
            '1_Demande_de_transmission_d_une_copie_d_un_DPE.docx',
            '2_Information_au_bailleur_Mise_en_conformite.docx',
            '3_Mise_en_demeure.docx',
            '4_Invitation_a_contacter_l_ADIL.docx',
            '5_Engagement_du_bailleur_a_realiser_des_travaux.docx',
            '6_Saisine_de_la_Commission_departementale_de_conciliation.docx',
        ] as $filename) {
            $this->connection->update('file', [
                'partner_competence' => null,
                'description' => null,
                'document_type' => DocumentType::AUTRE->value,
            ], ['filename' => $filename]);
        }
    }
}
