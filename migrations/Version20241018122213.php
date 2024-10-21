<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241018122213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add signalement_id to history_entry';
    }

    public function up(Schema $schema): void
    {
        // ajout de la colonne
        $this->connection->executeStatement('ALTER TABLE history_entry ADD signalement_id INT DEFAULT NULL');

        // Exécution directe de la requête via la connexion Doctrine
        $this->connection->executeStatement('
            UPDATE history_entry he
            INNER JOIN affectation a ON he.entity_id = a.id
            SET he.signalement_id = a.signalement_id
            WHERE CAST(entity_name AS BINARY) = "App\\\\Entity\\\\Affectation"
        ');

        // définit le signalement_id pour toutes les entrées de type Affectation, donc l'affectation n'existe plus
        $this->connection->executeStatement('
            UPDATE history_entry he
            INNER JOIN signalement s ON s.uuid = (
                SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(he.source, "/affectation/", 1), "/signalements/", -1)
            )
            SET he.signalement_id = s.id
            WHERE CAST(he.entity_name AS BINARY) = "App\\\\Entity\\\\Affectation"
            AND he.signalement_id IS NULL
            AND he.source LIKE "%/signalements/%/affectation/%";
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE history_entry DROP signalement_id');
    }
}
