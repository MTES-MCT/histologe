<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241217195812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create view to get latest intervention';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE OR REPLACE VIEW view_latest_intervention AS
                SELECT
                    i.signalement_id,
                    i.conclude_procedure,
                    i.details,
                    i.occupant_present,
                    i.scheduled_at,
                    i.status,
                    (
                        SELECT COUNT(*)
                        FROM intervention i2
                        WHERE i2.signalement_id = i.signalement_id
                          AND i2.type = \'VISITE\'
                    ) AS nb_visites
                FROM
                    intervention i
                WHERE
                    i.type = \'VISITE\' AND
                    i.scheduled_at = (
                        SELECT
                            MAX(i2.scheduled_at)
                        FROM
                            intervention i2
                        WHERE
                            i2.signalement_id = i.signalement_id
                            AND i2.type = \'VISITE\'
                    );'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS view_latest_intervention');
    }
}
