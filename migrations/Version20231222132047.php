<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231222132047 extends AbstractMigration
{
    public const TERRITORY_ZIP_SARTHE = 72;

    public function getDescription(): string
    {
        return 'Remove Corrupted Imported Signalements from Sarthe';
    }

    public function up(Schema $schema): void
    {
        $territory = $this->connection->fetchAssociative('SELECT id FROM territory WHERE zip LIKE :zip', [
            'zip' => self::TERRITORY_ZIP_SARTHE,
        ]);

        $this->skipIf(!$territory, 'Datebase is empty');

        $parameters = [
            'territory_id' => $territory['id'],
            'is_imported' => 1,
        ];

        $this->addSql('DELETE FROM signalement_qualification WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM affectation WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM notification WHERE suivi_id IN (SELECT id FROM suivi WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported))', $parameters);

        $this->addSql('DELETE FROM suivi WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM signalement_criticite WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM signalement_critere WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM signalement_situation WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM signalement_usager WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM file WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM job_event WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM intervention WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM signalement_usager WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM tag_signalement WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM desordre_precision_signalement WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM desordre_critere_signalement WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM desordre_categorie_signalement WHERE signalement_id IN (
        SELECT id FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported)', $parameters);

        $this->addSql('DELETE FROM signalement WHERE territory_id = :territory_id AND is_imported = :is_imported', $parameters);
    }

    public function down(Schema $schema): void
    {
    }
}
