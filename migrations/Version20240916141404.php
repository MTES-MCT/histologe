<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240916141404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'migrate type_logement to nature_logement in signalement';
    }

    public function up(Schema $schema): void
    {
        // set nb_pieces_logement if not defined, if nature_logement is '1', '2', '3', '4' or '5 ou plus'
        $natureToNbPieces = [
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5 ou plus' => 5,
        ];
        foreach ($natureToNbPieces as $natureLogement => $nbPieces) {
            $this->addSql(
                'UPDATE signalement SET nb_pieces_logement = :nbPieces WHERE nature_logement = :natureLogement AND nb_pieces_logement IS NULL', [
                    'nbPieces' => $nbPieces,
                    'natureLogement' => $natureLogement,
                ]
            );
        }

        // set nature_logement to NULL if other than 'maison', 'appartement' or 'autre'
        $this->addSql('UPDATE `signalement` SET `nature_logement`=NULL WHERE `nature_logement` NOT IN (\'maison\',\'appartement\',\'autre\')');

        // set nature_logement to 'maison', 'appartement' or 'autre' if not defined, and if defined in 'type_logement'
        // set nature_logement to 'appartement' if not defined AND if type_logement is CHAMBRE, STUDIO, T1..T6, PLUS
        // set nature_logement to 'autre' if type_logement is 'BÂTIMENT' or 'IMMEUBLE'
        $typeToNature = [
            'MAISON' => 'maison',
            'APPARTEMENT' => 'appartement',
            'AUTRE' => 'autre',
            'CHAMBRE' => 'appartement',
            'STUDIO' => 'appartement',
            'T1' => 'appartement',
            'T2' => 'appartement',
            'T3' => 'appartement',
            'T4' => 'appartement',
            'T5' => 'appartement',
            'T6' => 'appartement',
            'BÂTIMENT' => 'autre',
            'IMMEUBLE' => 'autre',
        ];
        foreach ($typeToNature as $typeLogement => $natureLogement) {
            $this->addSql(
                'UPDATE signalement SET nature_logement = :natureLogement WHERE type_logement = :typeLogement AND nature_logement IS NULL', [
                    'typeLogement' => $typeLogement,
                    'natureLogement' => $natureLogement,
                ]
            );
        }

        // set nb_pieces_logement if not defined AND if type_logement is CHAMBRE, STUDIO, T1..T6
        $typeToNbPieces = [
            'CHAMBRE' => 1,
            'STUDIO' => 1,
            'T1' => 1,
            'T2' => 2,
            'T3' => 3,
            'T4' => 4,
            'T5' => 5,
            'T6' => 5,
        ];
        foreach ($typeToNbPieces as $typeLogement => $nbPieces) {
            $this->addSql(
                'UPDATE signalement SET nb_pieces_logement = :nbPieces WHERE type_logement = :typeLogement AND nb_pieces_logement IS NULL', [
                    'typeLogement' => $typeLogement,
                    'nbPieces' => $nbPieces,
                ]
            );
        }

        // remove col type_logement
        $this->addSql('ALTER TABLE signalement DROP type_logement');
    }

    public function down(Schema $schema): void
    {
    }
}
