<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240411120500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Met à jour le desordreSlug des file suite à un bug de la MEP du 4 avril';
    }

    public function up(Schema $schema): void
    {
        $files = $this->connection->executeQuery('
            SELECT f.id, f.signalement_id, f.filename
            FROM file f
            WHERE f.document_type = "PHOTO_SITUATION"
            AND f.created_at > "2024-04-04 12:00:00"
            AND f.desordre_slug IS NULL
        ')->fetchAllAssociative();

        foreach ($files as $file) {
            $signalementDraftData = $this->connection->executeQuery('
                SELECT sd.payload
                FROM signalement_draft sd
                JOIN signalement s ON s.created_from_id = sd.id
                WHERE s.id = :signalementId
            ', ['signalementId' => $file['signalement_id']])->fetchAssociative();

            $desordreSlug = $this->extractDesordreSlugFromPayload($signalementDraftData['payload'], $file['filename']);

            $this->connection->executeStatement('
                UPDATE file
                SET desordre_slug = :desordreSlug
                WHERE id = :fileId
            ', ['desordreSlug' => $desordreSlug, 'fileId' => $file['id']]);
        }
    }

    private function extractDesordreSlugFromPayload(string $payload, string $filename): ?string
    {
        $jsonData = json_decode($payload, true);

        foreach ($jsonData as $key => $value) {
            if (\is_array($value) && isset($value[0]['file'])) {
                foreach ($value as $photo) {
                    if ($photo['file'] === $filename) {
                        $key = str_replace('_details_photos_upload', '', $key);
                        $key = str_replace('_photos_upload', '', $key);

                        return $key;
                    }
                }
            }
        }

        return null;
    }

    public function down(Schema $schema): void
    {
    }
}
