<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\MotifClotureUsager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224143705 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add motif_cloture_usager to signalement';
    }

    public function up(Schema $schema): void
    {
        // $this->addSql('ALTER TABLE signalement ADD motif_cloture_usager VARCHAR(255) DEFAULT NULL');
        $this->connection->executeStatement(
            'ALTER TABLE signalement ADD motif_cloture_usager VARCHAR(255) DEFAULT NULL'
        );

        $connection = $this->connection;

        $this->processCategory(
            $connection,
            'DEMANDE_ABANDON_PROCEDURE',
            '/pour le motif suivant :\s*(.*?)<br\s*\/?>/i'
        );

        $this->processCategory(
            $connection,
            'INJONCTION_BAILLEUR_CLOTURE_PAR_USAGER',
            '/pour le motif suivant :<br\s*\/?>\s*(.*?)<br\s*\/?>/i'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP motif_cloture_usager');
    }

    private function processCategory($connection, string $category, string $regex): void
    {
        $rows = $connection->fetchAllAssociative('
            SELECT s.id as signalement_id, su.description
            FROM signalement s
            INNER JOIN suivi su ON su.id = (
                SELECT su2.id
                FROM suivi su2
                WHERE su2.signalement_id = s.id
                  AND su2.category = :category
                ORDER BY su2.created_at DESC
                LIMIT 1
            )
            WHERE s.motif_cloture_usager IS NULL
        ', [
            'category' => $category,
        ]);

        foreach ($rows as $row) {
            $description = $row['description'];

            if (!preg_match($regex, $description, $matches)) {
                continue;
            }

            $label = html_entity_decode(strip_tags($matches[1]));
            $label = trim($label);

            $motif = MotifClotureUsager::tryFromLabel($label);

            if (null === $motif) {
                continue;
            }

            $connection->update(
                'signalement',
                ['motif_cloture_usager' => $motif->value],
                ['id' => $row['signalement_id']]
            );
        }
    }
}
