<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\PartnerType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230606161945 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add filed in order to select which signalement need to be sync';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation ADD is_synchronized TINYINT(1) NOT NULL');

        $sql = 'UPDATE affectation
                SET is_synchronized = :is_synchronised
                WHERE id IN (
                    SELECT DISTINCT subquery.id
                    FROM (
                        SELECT a.id
                        FROM affectation a
                        INNER JOIN job_event j ON j.signalement_id = a.signalement_id AND j.partner_id = a.partner_id
                        WHERE j.partner_type LIKE :partner_type OR j.partner_type IS NULL
                    ) As subquery
                )';

        $parameters = [
            'is_synchronised' => true,
            'partner_type' => PartnerType::COMMUNE_SCHS->value,
        ];
        $this->addSql($sql, $parameters);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation DROP is_synchronized');
    }
}
