<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\AffectationStatus;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250619151216 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change affectation status format';
    }

    private const OLD_STATUS_TO_NEW_STATUS = [
        AffectationStatus::WAIT->value => 0,
        AffectationStatus::ACCEPTED->value => 1,
        AffectationStatus::REFUSED->value => 2,
        AffectationStatus::CLOSED->value => 3,
    ];

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation CHANGE statut statut VARCHAR(255) NOT NULL');

        foreach (self::OLD_STATUS_TO_NEW_STATUS as $newStatus => $oldStatus) {
            $this->addSql('UPDATE `affectation` SET `statut` = \''.$newStatus.'\' WHERE statut = \''.$oldStatus.'\'');
        }

        $queryOld = $this->getQueryUpdateHistoryUp('old');
        $this->addSql($queryOld);

        $queryNew = $this->getQueryUpdateHistoryUp('new');
        $this->addSql($queryNew);
    }

    private function getQueryUpdateHistoryUp($field): string
    {
        return "
            UPDATE history_entry
            SET changes = JSON_SET(
                changes,
                '$.statut.".$field."',
                CASE JSON_UNQUOTE(JSON_EXTRACT(changes, '$.statut.".$field."'))
                    WHEN '0' THEN 'NOUVEAU'
                    WHEN '1' THEN 'EN_COURS'
                    WHEN '2' THEN 'REFUSE'
                    WHEN '3' THEN 'FERME'
                    ELSE JSON_UNQUOTE(JSON_EXTRACT(changes, '$.statut.".$field."'))
                END
            )
            WHERE entity_name LIKE '%Affectation'
            AND event = 'UPDATE'
            AND source LIKE '%response%'
            AND JSON_EXTRACT(changes, '$.statut.".$field."') IS NOT NULL
            AND JSON_UNQUOTE(JSON_EXTRACT(changes, '$.statut.".$field."')) IN ('0','1','2','3')
        ";
    }

    public function down(Schema $schema): void
    {
        $queryOld = $this->getQueryUpdateHistoryDown('old');
        $this->addSql($queryOld);

        $queryNew = $this->getQueryUpdateHistoryDown('new');
        $this->addSql($queryNew);

        foreach (self::OLD_STATUS_TO_NEW_STATUS as $newStatus => $oldStatus) {
            $this->addSql('UPDATE `affectation` SET `statut` = \''.$oldStatus.'\' WHERE statut = \''.$newStatus.'\'');
        }

        $this->addSql('ALTER TABLE affectation CHANGE statut statut INT NOT NULL');
    }

    private function getQueryUpdateHistoryDown($field): string
    {
        return "
            UPDATE history_entry
            SET changes = JSON_SET(
                changes,
                '$.statut.".$field."',
                CASE JSON_UNQUOTE(JSON_EXTRACT(changes, '$.statut.".$field."'))
                    WHEN 'NOUVEAU' THEN '0'
                    WHEN 'EN_COURS' THEN '1'
                    WHEN 'REFUSE' THEN '2'
                    WHEN 'FERME' THEN '3'
                    ELSE JSON_UNQUOTE(JSON_EXTRACT(changes, '$.statut.".$field."'))
                END
            )
            WHERE entity_name LIKE '%Affectation'
            AND event = 'UPDATE'
            AND source LIKE '%response%'
            AND JSON_EXTRACT(changes, '$.statut.".$field."') IS NOT NULL
            AND JSON_UNQUOTE(JSON_EXTRACT(changes, '$.statut.".$field."')) IN ('NOUVEAU','EN_COURS','REFUSE','FERME')
        ";
    }
}
