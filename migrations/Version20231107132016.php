<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Entity\Suivi;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class Version20231107132016 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const SUIVI_DESCRIPTION = 'En cas de besoin concernant votre dossier, merci de vous rapprocher de l\'Adil 29 : 02.98.46.37.38. ou par mail : lhi29@adil29.org';
    public const TERRITORY_ZIP_FINISTERE = 29;

    public function getDescription(): string
    {
        return 'Cloture des signalements nouveau ou en cours sur le département finistere hors Quimper et Brest';
    }

    public function up(Schema $schema): void
    {
        $signalements = $this->connection->fetchAllAssociative('SELECT id, reference, uuid
        FROM signalement
        WHERE statut <= :statutActif
        AND territory_id IN (SELECT id FROM territory WHERE zip = :zipFinistere)
        AND ville_occupant != :villeQuimper
        AND ville_occupant != :villeBrest  ',
            [
                'statutActif' => Signalement::STATUS_NEED_PARTNER_RESPONSE,
                'zipFinistere' => self::TERRITORY_ZIP_FINISTERE,
                'villeQuimper' => 'Quimper',
                'villeBrest' => 'Brest',
            ]);
        $this->skipIf(!$signalements, 'No signalements to update');
        $adminEmail = $this->container->getParameter('user_system_email');
        $user = $this->connection->fetchAssociative(
            'SELECT id FROM user WHERE email LIKE :adminEmail', [
            'adminEmail' => $adminEmail,
        ]);

        foreach ($signalements as $signalement) {
            $signalementId = $signalement['id'];
            $this->addSql(<<<SQL
                UPDATE signalement
                SET motif_cloture = :motifCloture, statut = :closedStatus, closed_at = :closedAt
                WHERE id = :signalementId
            SQL, [
                'motifCloture' => MotifCloture::AUTRE->value,
                'closedStatus' => Signalement::STATUS_CLOSED,
                'closedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
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
}
