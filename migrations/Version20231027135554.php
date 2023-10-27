<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Affectation;
use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Entity\Suivi;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class Version20231027135554 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const SUIVI_DESCRIPTION = 'Sauvegarde d\'un signalement ancien concernant votre logement. Merci de ne pas répondre à ce message';
    public const TAG_LABEL = 'Sauvegarde';
    public const TERRITORY_ZIP_ISERE = 38;

    public function getDescription(): string
    {
        return 'Close imported signalement with active status AND without active partners';
    }

    public function up(Schema $schema): void
    {
        $adminEmail = $this->container->getParameter('user_system_email');
        $user = $this->connection->fetchAssociative(
            'SELECT id FROM user WHERE email LIKE :adminEmail', [
            'adminEmail' => $adminEmail,
        ]);

        $territory = $this->connection->fetchAssociative('SELECT id FROM territory WHERE zip LIKE :zipIsere', [
            'zipIsere' => self::TERRITORY_ZIP_ISERE,
        ]);
        $this->abortIf(!$territory, 'Datebase is empty');

        $signalements = $this->getSignalements($territory);
        $this->abortIf(!$signalements, 'No signalements to update for Isere');

        $tagId = $this->createOrGetTag($territory);
        $this->abortIf(!$tagId, sprintf('No tag %s for %s, please create ', self::TAG_LABEL, self::TERRITORY_ZIP_ISERE));

        foreach ($signalements as $signalement) {
            $signalementId = $signalement['id'];
            $this->addSql(<<<SQL
                UPDATE signalement
                SET motif_cloture = :motifCloture, statut = :closedStatus
                WHERE id = :signalementId
            SQL, [
                'motifCloture' => MotifCloture::AUTRE,
                'closedStatus' => Signalement::STATUS_CLOSED,
                'signalementId' => $signalementId,
            ]);

            $this->addSql(<<<SQL
                INSERT INTO suivi (created_by_id, created_at, description, is_public, signalement_id, type)
                VALUES (:createdById, :createdAt, :description, :isPublic, :signalementId, :type)
            SQL, [
                'createdById' => $user['id'],
                'createdAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'description' => self::SUIVI_DESCRIPTION,
                'isPublic' => 0,
                'signalementId' => $signalementId,
                'type' => Suivi::TYPE_TECHNICAL,
            ]);

            $this->addSql(<<<SQL
                INSERT INTO tag_signalement (tag_id, signalement_id)
                VALUES (:tagId, :signalementId)
            SQL, [
                'tagId' => $tagId,
                'signalementId' => $signalementId,
            ]);

            $this->write(sprintf(
                'Signalement %s has been updated, please check %s',
                $signalement['reference'],
                $this->container->getParameter('host_url').'/bo/signalements/'.$signalement['uuid']
            ));
        }

        $this->write(sprintf('%s signalements have been updated', \count($signalements)));
    }

    public function down(Schema $schema): void
    {
    }

    private function getSignalements(array $territory): array
    {
        return $this->connection->fetchAllAssociative(<<<SQL
            SELECT s.id, s.reference, s.uuid
            FROM signalement s
            WHERE s.territory_id = :territoryId
              AND s.is_imported = :isImported
              AND s.statut = :activeStatus
              AND NOT EXISTS (
                SELECT 1
                FROM affectation a
                WHERE a.signalement_id = s.id
                  AND a.statut NOT IN (:closedStatus, :refusedStatus)
              )
        SQL, [
            'territoryId' => $territory['id'],
            'isImported' => 1,
            'activeStatus' => Signalement::STATUS_ACTIVE,
            'closedStatus' => Affectation::STATUS_CLOSED,
            'refusedStatus' => Affectation::STATUS_REFUSED,
        ]);
    }

    private function createOrGetTag(array $territory): int
    {
        $tag = $this->connection->fetchAssociative(<<<SQL
            SELECT id
            FROM tag
            WHERE label LIKE :label AND is_archive = :isArchive AND territory_id = :territoryId
        SQL, [
            'label' => self::TAG_LABEL,
            'isArchive' => 0,
            'territoryId' => $territory['id'],
        ]);

        if (!$tag) {
            $this->connection->executeQuery(<<<SQL
                INSERT INTO tag (label, is_archive, territory_id)
                VALUES (:label, :isArchive, :territoryId)
            SQL, [
                'label' => self::TAG_LABEL,
                'isArchive' => 0,
                'territoryId' => $territory['id'],
            ]);

            $tag = $this->connection->fetchAssociative(<<<SQL
            SELECT id
            FROM tag
            WHERE label LIKE :label AND is_archive = :isArchive AND territory_id = :territoryId
        SQL, [
                'label' => self::TAG_LABEL,
                'isArchive' => 0,
                'territoryId' => $territory['id'],
            ]);
        }

        return $tag['id'];
    }
}
