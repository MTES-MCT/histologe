<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Service\AddressHelper;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720095445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'copy addresses of signalements to address table';
    }

    public function up(Schema $schema): void
    {
        // Récupérer tous les signalements avec leurs adresses occupant
        $signalements = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT
                s.adresse_occupant,
                s.cp_occupant,
                s.ville_occupant,
                s.insee_occupant,
                s.ban_id_occupant,
                s.geoloc,
                s.territory_id
            FROM signalement s
            WHERE s.adresse_occupant IS NOT NULL
                AND s.ville_occupant IS NOT NULL
                AND s.cp_occupant IS NOT NULL
                AND s.insee_occupant IS NOT NULL
                AND s.territory_id IS NOT NULL'
        );

        foreach ($signalements as $signalement) {
            // Extraire le numéro de rue et le nom de la rue depuis adresseOccupant
            [$housenumber, $street] = AddressHelper::getHouseNumberAndStreetFromAddress($signalement['adresse_occupant']);

            // Vérifier si l'adresse existe déjà (contrainte unique sur housenumber, street, city_code)
            $checkParams = [
                'street' => $street,
                'cityCode' => $signalement['insee_occupant'],
            ];

            if ($housenumber) {
                $checkParams['housenumber'] = $housenumber;
                $housenumberCondition = 'housenumber = :housenumber';
            } else {
                $housenumberCondition = 'housenumber IS NULL';
            }

            $existingAddress = $this->connection->fetchOne(
                'SELECT id FROM address
                WHERE '.$housenumberCondition.'
                AND street = :street
                AND city_code = :cityCode',
                $checkParams
            );

            // Insérer uniquement si l'adresse n'existe pas déjà
            if (!$existingAddress) {
                // Convertir geoloc JSON en point PostGIS
                $point = null;
                if (!empty($signalement['geoloc'])) {
                    $geoloc = json_decode($signalement['geoloc'], true);
                    if (isset($geoloc['lat']) && isset($geoloc['lng'])) {
                        $lat = $geoloc['lat'];
                        $lng = $geoloc['lng'];
                        $point = sprintf('POINT(%s %s)', $lng, $lat);
                    }
                }

                $insertData = [
                    'housenumber' => $housenumber,
                    'street' => $street,
                    'city' => $signalement['ville_occupant'],
                    'postCode' => $signalement['cp_occupant'],
                    'cityCode' => $signalement['insee_occupant'],
                    'banId' => $signalement['ban_id_occupant'],
                    'territoryId' => $signalement['territory_id'],
                ];

                if ($point) {
                    $this->addSql(
                        'INSERT INTO address (housenumber, street, city, post_code, city_code, ban_id, point, territory_id)
                        VALUES (:housenumber, :street, :city, :postCode, :cityCode, :banId, ST_GeomFromText(:point, 4326), :territoryId)',
                        array_merge($insertData, ['point' => $point])
                    );
                } else {
                    $this->addSql(
                        'INSERT INTO address (housenumber, street, city, post_code, city_code, ban_id, territory_id)
                        VALUES (:housenumber, :street, :city, :postCode, :cityCode, :banId, :territoryId)',
                        $insertData
                    );
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Cette migration est irréversible
        $this->abortIf(true, 'Cette migration ne peut pas être annulée.');
    }
}
