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
        $this->connection->update(
            'file',
            [
                'partner_competence' => Qualification::NON_DECENCE_ENERGETIQUE->value,
                'description' => 'Modèle de courrier pour demander une copie du DPE au bailleur / propriétaire. Courrier à compléter par l\'usager, modèle rédigé par l\'ANIL.',
                'document_type' => DocumentType::MODELE_DE_COURRIER->value,
                'title' => 'Demande de copie de DPE (non décence énergétique)',
            ],
            [
                'is_standalone' => 1,
                'filename' => '1_Demande_de_transmission_d_une_copie_d_un_DPE.docx',
            ]
        );

        $this->connection->update(
            'file',
            [
                'partner_competence' => Qualification::NON_DECENCE_ENERGETIQUE->value,
                'description' => 'Modèle de courrier pour informer le bailleur d\'un problème de performance énergétique et lui demander la mise en conformité. Courrier à compléter par l\'usager, modèle rédigé par l\'ANIL.',
                'document_type' => DocumentType::MODELE_DE_COURRIER->value,
                'title' => 'Information problème performance énergétique (non décence énergétique)',
            ],
            [
                'is_standalone' => 1,
                'filename' => '2_Information_au_bailleur_Mise_en_conformite.docx',
            ]
        );

        $this->connection->update(
            'file',
            [
                'partner_competence' => Qualification::NON_DECENCE_ENERGETIQUE->value,
                'description' => 'Modèle de mise en demeure du bailleur de mettre le logement en conformité, dans les cas de non décence énergétique. Courrier à compléter par l\'usager, modèle rédigé par l\'ANIL.',
                'document_type' => DocumentType::MODELE_DE_COURRIER->value,
                'title' => 'Mise en demeure (non décence énergétique)',
            ],
            [
                'is_standalone' => 1,
                'filename' => '3_Mise_en_demeure.docx',
            ]
        );

        $this->connection->update(
            'file',
            [
                'partner_competence' => Qualification::NON_DECENCE_ENERGETIQUE->value,
                'description' => 'Courrier à compléter et retourner au bailleur pour l\'inviter à contacter l\'ADIL, dans les cas de non décence énergétique.',
                'document_type' => DocumentType::MODELE_DE_COURRIER->value,
                'title' => 'Invitation du bailleur à contacter l\'ADIL (non décence énergétique)',
            ],
            [
                'is_standalone' => 1,
                'filename' => '4_Invitation_a_contacter_l_ADIL.docx',
            ]
        );

        $this->connection->update(
            'file',
            [
                'partner_competence' => Qualification::NON_DECENCE_ENERGETIQUE->value,
                'description' => 'Modèle de courrier d\'engagement à réalisation des travaux dans les cas de non décence énergétique. Courrier à compléter par le bailleur, modèle rédigé par l\'ANIL.',
                'document_type' => DocumentType::MODELE_DE_COURRIER->value,
                'title' => 'Engagement à réaliser des travaux (non décence énergétique)',
            ],
            [
                'is_standalone' => 1,
                'filename' => '5_Engagement_du_bailleur_a_realiser_des_travaux.docx',
            ]
        );

        $this->connection->update(
            'file',
            [
                'partner_competence' => Qualification::NON_DECENCE_ENERGETIQUE->value,
                'description' => 'Modèle de courrier de saisine de la Commission départementale de conciliation pour non décence énergétique du logement. Courrier à compléter par l\'usager, modèle rédigé par l\'ANIL.',
                'document_type' => DocumentType::MODELE_DE_COURRIER->value,
                'title' => 'Saisine de la Commission de conciliation (non décence énergétique)',
            ],
            [
                'is_standalone' => 1,
                'filename' => '6_Saisine_de_la_Commission_departementale_de_conciliation.docx',
            ]
        );
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
                'title' => str_replace('_', ' ', $filename),
            ], ['filename' => $filename]);
        }
    }
}
