<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250225095613 extends AbstractMigration
{
    public const string METROPOLE_DE_LYON_TERRITORY_ZIP = '69A';

    public const array METROPOLE_DE_LYON_INSEE_LIST = [
        69091, 69096, 69123, 69149, 69199, 69205, 69290, 69259, 69266, 69381, 69382, 69383, 69384,
        69385, 69386, 69387, 69388, 69389, 69003, 69029, 69033, 69034, 69040, 69044, 69046, 69271,
        69063, 69273, 69068, 69069, 69071, 69072, 69275, 69081, 69276, 69085, 69087, 69088, 69089,
        69100, 69279, 69142, 69250, 69116, 69117, 69127, 69282, 69283, 69284, 69143, 69152, 69153,
        69163, 69286, 69168, 69191, 69194, 69204, 69207, 69202, 69292, 69293, 69296, 69244, 69256,
        69260, 69233, 69278,
    ];
    public const array RHONE_INSEE_LIST = [
        69001, 69006, 69008, 69037, 69054, 69060, 69066, 69070, 69075, 69093, 69102, 69107, 69174,
        69130, 69160, 69164, 69169, 69181, 69183, 69188, 69200, 69214, 69217, 69225, 69229, 69234,
        69240, 69243, 69248, 69254, 69157,
    ];
    public const int RHONE_TERRITORY_ID = 70;

    public function getDescription(): string
    {
        return 'Split the territory of Rhône into Rhône and Métropole de Lyon';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            'prod' !== getenv('APP_ENV'),
            'Cette migration ne s’exécute qu’en environnement de production.'
        );
        // delete inconsistent tags from signalent
        $this->addSql('DELETE ts FROM tag_signalement ts
            INNER JOIN tag t ON t.id = ts.tag_id
            INNER JOIN signalement s ON s.id = ts.signalement_id
            WHERE t.territory_id != s.territory_id
        ');
        // add metropole de lyon
        $this->connection->executeStatement(
            "INSERT INTO `territory` (`id`, `zip`, `name`, `is_active`, `bbox`, `authorized_codes_insee`, `timezone`, `grille_visite_filename`, `is_grille_visite_disabled`)
                  VALUES (NULL, '".self::METROPOLE_DE_LYON_TERRITORY_ZIP."', 'Métropole de Lyon', '1', '[]', '".json_encode(self::METROPOLE_DE_LYON_INSEE_LIST)."', 'Europe/Paris', NULL, '1')"
        );
        $metropoleTerritoryId = $this->connection->lastInsertId();
        // update rhone
        $this->addSql(
            "UPDATE `territory` SET `authorized_codes_insee` = '".json_encode(self::RHONE_INSEE_LIST)."' WHERE `territory`.`id` = :rhone_id",
            ['rhone_id' => self::RHONE_TERRITORY_ID]
        );
        // copy bailleur
        $bailleurs = $this->getBailleur();
        foreach ($bailleurs as $bailleur) {
            $this->addSql('INSERT INTO `bailleur_territory` (`bailleur_id`, `territory_id`) VALUES (:bailleur_id, :metropole_id)', ['bailleur_id' => $bailleur['id'], 'metropole_id' => $metropoleTerritoryId]);
        }
        // update signalement
        $this->addSql(
            'UPDATE signalement SET territory_id = :metropole_id WHERE territory_id = :rhone_id AND insee_occupant IN ('.implode(',', self::METROPOLE_DE_LYON_INSEE_LIST).')',
            ['metropole_id' => $metropoleTerritoryId, 'rhone_id' => self::RHONE_TERRITORY_ID]
        );
        // update commune
        $this->addSql(
            'UPDATE commune SET territory_id = :metropole_id WHERE territory_id = :rhone_id AND code_insee IN ('.implode(',', self::METROPOLE_DE_LYON_INSEE_LIST).')',
            ['metropole_id' => $metropoleTerritoryId, 'rhone_id' => self::RHONE_TERRITORY_ID]
        );
        // update affectation
        $this->addSql(
            'UPDATE affectation a INNER JOIN signalement s ON s.id = a.signalement_id SET a.territory_id = :metropole_id WHERE s.territory_id = :metropole_id',
            ['metropole_id' => $metropoleTerritoryId]
        );
        // update partenaire
        foreach (self::METROPOLE_DE_LYON_INSEE_LIST as $insee) {
            $this->addSql(
                'UPDATE partner SET territory_id = :metropole_id WHERE territory_id = :rhone_id AND insee LIKE :insee',
                ['metropole_id' => $metropoleTerritoryId, 'rhone_id' => self::RHONE_TERRITORY_ID, 'insee' => '%"'.$insee.'"%']
            );
        }
        // delete affectation 142 because it's a test becoming inconsistent after the split
        $this->addSql('DELETE FROM affectation WHERE id = 142');
    }

    private function getBailleur(): array
    {
        $query = 'SELECT DISTINCT(b.id) as id FROM bailleur b INNER JOIN bailleur_territory bt ON b.id = bt.bailleur_id AND bt.territory_id = :rhone_id';

        return $this->connection->fetchAllAssociative($query, ['rhone_id' => self::RHONE_TERRITORY_ID]);
    }

    public function down(Schema $schema): void
    {
    }
}
