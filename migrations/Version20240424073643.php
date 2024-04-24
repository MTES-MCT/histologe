<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240424073643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update link document message de suivi';
    }

    public function up(Schema $schema): void
    {
        $filesToUpdate = [
            '1_Demande_de_transmission_d_une_copie_d_un_DPE.docx',
            '2_Information_au_bailleur_Mise_en_conformite.docx',
            '3_Mise_en_demeure.docx',
            '4_Invitation_a_contacter_l_ADIL.docx',
            '5_Engagement_du_bailleur_a_realiser_des_travaux.docx',
            '6_Saisine_de_la_Commission_departementale_de_conciliation.docx',
        ];

        foreach ($filesToUpdate as $file) {
            $this->addSql(<<<SQL
            UPDATE suivi
            SET description = REPLACE(description, '$file?t=___TOKEN___', '$file')
            WHERE description LIKE '%$file?t=___TOKEN___%';
            SQL);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
