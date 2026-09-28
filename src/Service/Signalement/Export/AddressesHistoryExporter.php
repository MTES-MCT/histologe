<?php

namespace App\Service\Signalement\Export;

use App\Dto\Request\Signalement\AddressesHistorySearchQuery;
use App\Entity\User;
use App\Repository\Query\Address\AddressesHistoryQuery;
use App\Utils\ExportFormat;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

readonly class AddressesHistoryExporter
{
    public function __construct(
        private AddressesHistoryQuery $addressesHistoryQuery,
    ) {
    }

    public function write(
        User $user,
        string $format,
        string $outputFilePath,
        ?AddressesHistorySearchQuery $searchQuery = null,
    ): void {
        $rows = $this->addressesHistoryQuery->findAllAddressesWithHistory($user, $searchQuery);
        $addresses = $this->buildAddressRows($rows);
        $maxArretes = $this->getMaxArreteCount($addresses);

        $writer = ExportFormat::FORMAT_XLSX === $format
            ? new XlsxWriter()
            : new CsvWriter(new CsvOptions(FIELD_DELIMITER: ExportFormat::CSV_SEPARATOR));

        $writer->openToFile($outputFilePath);
        $writer->addRow(Row::fromValues($this->buildHeaders($maxArretes)));

        foreach ($addresses as $address) {
            $writer->addRow(Row::fromValues($this->buildRowValues($address, $maxArretes)));
        }

        $writer->close();
    }

    /**
     * Regroupe les lignes brutes (une par couple signalement/arrêté) par adresse.
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildAddressRows(array $rows): array
    {
        $addresses = [];

        foreach ($rows as $row) {
            $addressId = $row['addressId'];

            if (!isset($addresses[$addressId])) {
                $addresses[$addressId] = [
                    'adresse' => mb_trim($row['housenumber'].' '.$row['street']),
                    'codePostal' => $row['postCode'],
                    'commune' => $row['city'],
                    'references' => [],
                    'arretes' => [],
                    'bailleur' => null,
                    'syndicat' => null,
                    'hasLogementSocial' => false,
                    'hasLogementPrive' => false,
                    'processedSignalements' => [],
                    'processedArretes' => [],
                ];
            }

            if (!empty($row['id']) && !\in_array($row['id'], $addresses[$addressId]['processedSignalements'], true)) {
                $addresses[$addressId]['processedSignalements'][] = $row['id'];
                $addresses[$addressId]['references'][] = $row['reference'];

                if (true === $row['isLogementSocial']) {
                    $addresses[$addressId]['hasLogementSocial'] = true;
                } elseif (false === $row['isLogementSocial']) {
                    $addresses[$addressId]['hasLogementPrive'] = true;
                }

                if (null === $addresses[$addressId]['bailleur']) {
                    $addresses[$addressId]['bailleur'] = $this->getBailleurLabel($row);
                }

                if (null === $addresses[$addressId]['syndicat'] && !empty($row['denominationSyndic'])) {
                    $addresses[$addressId]['syndicat'] = $row['denominationSyndic'];
                }
            }

            if (!empty($row['arreteId']) && !\in_array($row['arreteId'], $addresses[$addressId]['processedArretes'], true)) {
                $addresses[$addressId]['processedArretes'][] = $row['arreteId'];
                $addresses[$addressId]['arretes'][] = [
                    'type' => null !== $row['arreteType'] ? $row['arreteType']->completeLabel() : null,
                    'date' => $row['dateArrete'] ? $row['dateArrete']->format('d/m/Y') : null,
                    'mainLevee' => $row['dateMainLevee'] ? $row['dateMainLevee']->format('d/m/Y') : null,
                ];
            }
        }

        return array_values($addresses);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function getBailleurLabel(array $row): ?string
    {
        if (!empty($row['bailleurName'])) {
            return $row['bailleurName'];
        }
        if (!empty($row['denominationProprio'])) {
            return $row['denominationProprio'];
        }
        if (!empty($row['nomProprio'])) {
            return mb_trim($row['nomProprio'].' '.$row['prenomProprio']);
        }

        return null;
    }

    /** @param array<int, array<string, mixed>> $addresses */
    private function getMaxArreteCount(array $addresses): int
    {
        $max = 0;
        foreach ($addresses as $address) {
            $max = max($max, \count($address['arretes']));
        }

        return $max;
    }

    /** @return array<int, string> */
    private function buildHeaders(int $maxArretes): array
    {
        $headers = ['Adresse', 'Code postal', 'Commune', 'Nombre de dossiers', 'Références des dossiers'];

        for ($i = 1; $i <= $maxArretes; ++$i) {
            $headers[] = 'Arrêté '.$i;
            $headers[] = 'Date de l\'arrêté '.$i;
            $headers[] = 'Main levée '.$i;
        }

        $headers[] = 'Bailleur';
        $headers[] = 'Syndicat';
        $headers[] = 'Nature du parc';

        return $headers;
    }

    /**
     * @param array<string, mixed> $address
     *
     * @return array<int, mixed>
     */
    private function buildRowValues(array $address, int $maxArretes): array
    {
        $values = [
            $address['adresse'],
            $address['codePostal'],
            $address['commune'],
            \count($address['references']),
            implode(' / ', $address['references']),
        ];

        for ($i = 0; $i < $maxArretes; ++$i) {
            $arrete = $address['arretes'][$i] ?? null;
            $values[] = $arrete['type'] ?? '';
            $values[] = $arrete['date'] ?? '';
            $values[] = null !== $arrete ? ($arrete['mainLevee'] ?? '/') : '';
        }

        $values[] = $address['bailleur'] ?? '';
        $values[] = $address['syndicat'] ?? '';
        $values[] = $this->getNatureParcLabel($address);

        return $values;
    }

    /** @param array<string, mixed> $address */
    private function getNatureParcLabel(array $address): string
    {
        $labels = [];
        if ($address['hasLogementSocial']) {
            $labels[] = 'Public';
        }
        if ($address['hasLogementPrive']) {
            $labels[] = 'Privé';
        }

        return empty($labels) ? 'Non renseigné' : implode(' / ', $labels);
    }
}
