<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\ArreteCreationSource;
use App\Entity\Enum\ArreteType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261023150901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Copie des interventions de type ARRETE_PREFECTORAL dans la table arrete';
    }

    public function up(Schema $schema): void
    {
        $interventions = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT i.id, i.signalement_id, i.scheduled_at, i.provider_id, i.additional_information, s.address_id
            FROM intervention i
            INNER JOIN signalement s ON s.id = i.signalement_id
            WHERE i.type = 'ARRETE_PREFECTORAL'
            ORDER BY i.id
            SQL);

        foreach ($interventions as $intervention) {
            $information = json_decode((string) $intervention['additional_information'], true) ?? [];
            $arreteType = ArreteType::fromEsaboraLabel((string) $information['arrete_type']);
            $dateMainLevee = isset($information['arrete_mainlevee_date']) ? \DateTimeImmutable::createFromFormat('!d/m/Y', $information['arrete_mainlevee_date']) ?: null : null;

            $this->addSql(
                'INSERT INTO arrete (address_id, date_arrete, arrete_type, date_main_levee, additional_information, arrete_creation_source, signalement_id, provider_id)
                VALUES (:address_id, :date_arrete, :arrete_type, :date_main_levee, :additional_information, :arrete_creation_source, :signalement_id, :provider_id)',
                [
                    'address_id' => $intervention['address_id'],
                    'date_arrete' => (new \DateTimeImmutable($intervention['scheduled_at']))->format('Y-m-d'),
                    'arrete_type' => $arreteType->value,
                    'date_main_levee' => $dateMainLevee?->format('Y-m-d'),
                    'additional_information' => json_encode([
                        'arrete_numero' => $information['arrete_numero'] ?? null,
                        'arrete_mainlevee_numero' => $information['arrete_mainlevee_numero'] ?? null,
                        'arrete_modificatif_date' => $information['arrete_modificatif_date'] ?? null,
                        'arrete_modificatif_numero' => $information['arrete_modificatif_numero'] ?? null,
                    ], \JSON_THROW_ON_ERROR),
                    'arrete_creation_source' => ArreteCreationSource::ESABORA->value,
                    'signalement_id' => $intervention['signalement_id'],
                    'provider_id' => $intervention['provider_id'],
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // les interventions d'origine sont conservées dans un premier temps.
    }
}
