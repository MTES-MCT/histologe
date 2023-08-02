<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230802101020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update signalement which should be set with is_not_occupant=true';
    }

    public function up(Schema $schema): void
    {
        $sql = <<<SQL
                UPDATE signalement
                SET is_not_occupant = TRUE
                WHERE statut = 2
                AND is_not_occupant = FALSE
                AND mail_declarant IS NOT NULL
                AND mail_declarant <> ''
                AND is_imported = FALSE
               SQL;
        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
        $uuids = [
            '5170872e-ecc3-4261-8e74-da0c25a7520e',
            'fd93f651-2764-4cab-b73b-76b9cc3eab87',
            '62bda4dd632ad',
            '621dec2e217e6',
            '620d25481b271',
            '620d2544b3a80',
            '620d2543391cc',
            '620d2541ac10b',
            '620d254115bbb',
            '620d2536dddf2',
            '620d2535d8a57',
            '620d253345242',
            '620d251b7e7e1',
            '620d250d14814',
            '620d250aedbd3',
            '620d2509c34dc',
            '620d24fd8ce2f',
            '620d24f19e99d',
            '620d24ec529e6',
            '620d24e8718f1',
            '620d24e42c0c3',
            '620d24e3e2451',
            '620d24e16d690',
            '620d24e028238',
            '620d24dbef3a9',
            '620d24db294b7',
            '620d24c4ec01c',
            '620d24c31d6b6',
            '620d24af93eff',
            '620d24ae5ae6b',
            '621debf3e0986',
            '621debf3b18d5',
            '621debf33876f',
            '620d24aabc752',
            '620d24a1639d2',
            '620d249f0f9be',
            '620d249da6898',
            '620d249ae8043',
            '620d2499c585d',
            '620d249909f0a',
            '620d2492e61c5',
            '620d248ecd1d8',
        ];
        $uuidsString = "'".implode("','", $uuids)."'";
        $this->addSql("UPDATE signalement SET is_not_occupant = FALSE WHERE uuid IN ($uuidsString)");
    }
}
