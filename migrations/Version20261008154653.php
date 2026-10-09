<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261008154653 extends AbstractMigration
{
    private const array DESCRIPTIONS = [
        'INJONCTION_BAILLEUR_REPONSE_OUI' => 'Le bailleur s\'engage à résoudre les désordres signalés.',
        'INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE' => 'Le bailleur s\'engage à résoudre les désordres signalés.',
        'INJONCTION_BAILLEUR_REPONSE_OUI_DEMARCHES_COMMENCEES' => 'Le bailleur s\'engage à résoudre les désordres signalés, et indique que les démarches ont déjà commencé.',
        'INJONCTION_BAILLEUR_REPONSE_NON' => 'Le bailleur refuse de résoudre les désordres signalés, le signalement va être pris en charge par les partenaires compétents.',
        'INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR' => 'Le bailleur souhaite arrêter la procédure d\'injonction, le signalement va être pris en charge par les partenaires compétents.',
    ];

    public function getDescription(): string
    {
        return 'Empty description in suivi table for INJONCTION_BAILLEUR_REPONSE_* and INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR categories';
    }

    public function up(Schema $schema): void
    {
        foreach (array_keys(self::DESCRIPTIONS) as $category) {
            $this->addSql('UPDATE suivi SET description = \'\' WHERE category = :category', ['category' => $category]);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::DESCRIPTIONS as $category => $description) {
            $this->addSql(
                'UPDATE suivi SET description = :description WHERE category = :category',
                ['description' => $description, 'category' => $category]
            );
        }
    }
}
