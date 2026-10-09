<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261009100808 extends AbstractMigration
{
    private const array DESCRIPTIONS = [
        'INJONCTION_BAILLEUR_RAPPEL_REPONSE_BAILLEUR' => 'Le bailleur du logement a été relancé pour répondre à l\'injonction.',
        'INJONCTION_BAILLEUR_REMINDER_FOR_BAILLEUR' => 'Relance envoyée au bailleur pour demander un suivi sur les travaux.',
        'INJONCTION_BAILLEUR_REMINDER_FOR_USAGER' => 'Important - Point d\'avancement mensuel : Merci d\'indiquer si des démarches ont été entamées par votre bailleur (devis reçus, rdv artisans, travaux débutés, aucune avancée...).',
        'INJONCTION_BAILLEUR_CLOTURE_SANS_ACTIVITE' => 'Sans suivi des parties, locataire et bailleur, nous procédons à la clôture du dossier',
        'INJONCTION_BAILLEUR_EXPIREE' => 'La procédure d’injonction a expiré pour le bailleur. Le signalement est désormais en attente de validation.',
    ];

    public function getDescription(): string
    {
        return 'Empty description in suivi table for INJONCTION_BAILLEUR_RAPPEL_REPONSE_BAILLEUR, INJONCTION_BAILLEUR_REMINDER_FOR_*, INJONCTION_BAILLEUR_CLOTURE_SANS_ACTIVITE and INJONCTION_BAILLEUR_EXPIREE categories';
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
