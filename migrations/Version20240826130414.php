<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240826130414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recréer les tags pour le territoire 62';
    }

    public function up(Schema $schema): void
    {
        // Récupérer l'ID du territoire où le zip est 62
        $territory = $this->connection->fetchAssociative(
            'SELECT id FROM territory WHERE zip = :zip',
            ['zip' => 62]
        );

        if (!$territory) {
            return;
        }

        $territoryId = $territory['id'];

        $this->connection->beginTransaction();

        try {
            // Récupère tous les tags concaténés par un | , non-archivés pour le territoire 62
            $tags = $this->connection->fetchAllAssociative(
                "SELECT * FROM tag WHERE is_archive = 0 AND territory_id = :territory_id AND label LIKE '%|%'",
                ['territory_id' => $territoryId]
            );

            foreach ($tags as $tag) {
                $tagId = $tag['id'];
                // Récupère les signalements liés à ce tag concaténé
                $signalementIds = $this->connection->fetchAllAssociative(
                    'SELECT signalement_id FROM tag_signalement WHERE tag_id = :tag_id',
                    ['tag_id' => $tagId]
                );

                // Récupère les différents labels des tags
                $labelParts = explode('|', $tag['label']);
                foreach ($labelParts as $label) {
                    $label = trim($label);
                    // On regarde si un tag avec ce label existe déjà pour ce label (dans le territoire)
                    $existingTag = $this->connection->fetchAssociative(
                        'SELECT id FROM tag WHERE label = :label AND territory_id = :territory_id AND is_archive = 0',
                        ['label' => $label, 'territory_id' => $territoryId]
                    );

                    if ($existingTag) {
                        $newTagId = $existingTag['id'];
                    } else {
                        // s'il n'existe pas, on le créé
                        $this->connection->executeStatement(
                            'INSERT INTO tag (label, is_archive, territory_id) VALUES (:label, 0, :territory_id)',
                            ['label' => $label, 'territory_id' => $territoryId]
                        );
                        // Récupérer l'ID du tag inséré directement
                        $newTagId = $this->connection->fetchOne(
                            'SELECT id FROM tag WHERE label = :label AND territory_id = :territory_id AND is_archive = 0',
                            ['label' => $label, 'territory_id' => $territoryId]
                        );

                        if (!$newTagId) {
                            throw new \RuntimeException('Failed to retrieve the tag ID after insertion.');
                        }
                    }

                    // On lie tous les signalements liés au tag concaténé d'origine à ce tag créé
                    foreach ($signalementIds as $signalement) {
                        $this->addSql(
                            'INSERT INTO tag_signalement (tag_id, signalement_id) VALUES (:tag_id, :signalement_id)
                            ON DUPLICATE KEY UPDATE tag_id = tag_id',
                            ['tag_id' => $newTagId, 'signalement_id' => $signalement['signalement_id']]
                        );
                    }
                }

                // archive le tag concaté d'origine
                $this->addSql('UPDATE tag SET is_archive = 1 WHERE id = :id', ['id' => $tagId]);
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function down(Schema $schema): void
    {
    }
}
