<?php

namespace App\Tests\Unit\Service\Signalement\Export;

use App\Entity\Enum\ArreteType;
use App\Entity\User;
use App\Repository\Query\Address\AddressesHistoryQuery;
use App\Service\Signalement\Export\AddressesHistoryExporter;
use App\Tests\UserHelper;
use App\Utils\ExportFormat;
use OpenSpout\Reader\CSV\Options as CsvReaderOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddressesHistoryExporterTest extends TestCase
{
    use UserHelper;

    #[DataProvider('provideFileFormat')]
    public function testWrite(string $format): void
    {
        $user = $this->getUserFromRole(User::ROLE_ADMIN);

        /** @var MockObject&AddressesHistoryQuery */
        $addressesHistoryQuery = $this->createMock(AddressesHistoryQuery::class);
        $addressesHistoryQuery->expects($this->once())
            ->method('findAllAddressesWithHistory')
            ->with($user, null)
            ->willReturn($this->getRawRows());

        $tmpFile = sys_get_temp_dir().'/addresses_export_test_'.uniqid().'.'.$format;

        $exporter = new AddressesHistoryExporter($addressesHistoryQuery);
        $exporter->write($user, $format, $tmpFile);

        $rows = $this->readFile($tmpFile, $format);
        unlink($tmpFile);

        // L'adresse avec le plus d'arrêtés (2) détermine le nombre de colonnes dynamiques
        $this->assertSame(
            [
                'Adresse', 'Code postal', 'Commune', 'Nombre de dossiers', 'Références des dossiers',
                'Arrêté 1', 'Date de l\'arrêté 1', 'Main levée 1',
                'Arrêté 2', 'Date de l\'arrêté 2', 'Main levée 2',
                'Bailleur', 'Syndicat', 'Nature du parc',
            ],
            $rows[0]
        );

        // 1ère adresse : 2 dossiers, 1 arrêté sans main levée, bailleur social
        $this->assertSame('10 Rue de la Paix', $rows[1][0]);
        $this->assertSame('75002', $rows[1][1]);
        $this->assertSame('Paris', $rows[1][2]);
        $this->assertSame(2, (int) $rows[1][3]);
        $this->assertSame('2024-01 / 2024-02', $rows[1][4]);
        $this->assertSame('Mise en sécurité procédure ordinaire', $rows[1][5]);
        $this->assertSame('/', $rows[1][7]);
        $this->assertSame('', $rows[1][8]); // pas de 2e arrêté pour cette adresse
        $this->assertSame('Habitat 44', $rows[1][11]);
        $this->assertSame('', $rows[1][12]);
        $this->assertSame('Public', $rows[1][13]);

        // 2e adresse : 1 dossier, 2 arrêtés, propriétaire privé, une main levée renseignée
        $this->assertSame('5 Avenue Foch', $rows[2][0]);
        $this->assertSame(1, (int) $rows[2][3]);
        $this->assertSame('2024-03', $rows[2][4]);
        $this->assertSame('Impropre (Arrêté L.511-11)', $rows[2][5]);
        $this->assertSame('05/03/2024', $rows[2][7]);
        $this->assertSame('Ordinaire irrémédiable (Arrêté L.511-11)', $rows[2][8]);
        $this->assertSame('/', $rows[2][10]);
        $this->assertSame('Dupont Jean', $rows[2][11]);
        $this->assertSame('Privé', $rows[2][13]);
    }

    public static function provideFileFormat(): \Generator
    {
        yield 'export with xlsx' => [ExportFormat::FORMAT_XLSX];
        yield 'export with csv' => [ExportFormat::FORMAT_CSV];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getRawRows(): array
    {
        return [
            // Adresse 1 : 2 signalements x 1 arrêté (produit cartésien tel que renvoyé par la requête)
            [
                'addressId' => 1, 'housenumber' => '10', 'street' => 'Rue de la Paix',
                'postCode' => '75002', 'city' => 'Paris',
                'id' => 101, 'reference' => '2024-01', 'isLogementSocial' => true,
                'bailleurName' => 'Habitat 44', 'denominationProprio' => null,
                'nomProprio' => null, 'prenomProprio' => null, 'denominationSyndic' => null,
                'arreteId' => 901, 'arreteType' => ArreteType::MISE_EN_SECURITE,
                'dateArrete' => new \DateTimeImmutable('2024-01-10'), 'dateMainLevee' => null,
            ],
            [
                'addressId' => 1, 'housenumber' => '10', 'street' => 'Rue de la Paix',
                'postCode' => '75002', 'city' => 'Paris',
                'id' => 102, 'reference' => '2024-02', 'isLogementSocial' => true,
                'bailleurName' => 'Habitat 44', 'denominationProprio' => null,
                'nomProprio' => null, 'prenomProprio' => null, 'denominationSyndic' => null,
                'arreteId' => 901, 'arreteType' => ArreteType::MISE_EN_SECURITE,
                'dateArrete' => new \DateTimeImmutable('2024-01-10'), 'dateMainLevee' => null,
            ],
            // Adresse 2 : 1 signalement x 2 arrêtés (même produit cartésien), propriétaire privé
            [
                'addressId' => 2, 'housenumber' => '5', 'street' => 'Avenue Foch',
                'postCode' => '75116', 'city' => 'Paris',
                'id' => 201, 'reference' => '2024-03', 'isLogementSocial' => false,
                'bailleurName' => null, 'denominationProprio' => null,
                'nomProprio' => 'Dupont', 'prenomProprio' => 'Jean', 'denominationSyndic' => null,
                'arreteId' => 902, 'arreteType' => ArreteType::ARRETE_L_511_11_IMPROPRE,
                'dateArrete' => new \DateTimeImmutable('2024-02-15'), 'dateMainLevee' => new \DateTimeImmutable('2024-03-05'),
            ],
            [
                'addressId' => 2, 'housenumber' => '5', 'street' => 'Avenue Foch',
                'postCode' => '75116', 'city' => 'Paris',
                'id' => 201, 'reference' => '2024-03', 'isLogementSocial' => false,
                'bailleurName' => null, 'denominationProprio' => null,
                'nomProprio' => 'Dupont', 'prenomProprio' => 'Jean', 'denominationSyndic' => null,
                'arreteId' => 903, 'arreteType' => ArreteType::ARRETE_L_511_11_ORDINAIRE_IRREMEDIABLE,
                'dateArrete' => new \DateTimeImmutable('2024-02-20'), 'dateMainLevee' => null,
            ],
        ];
    }

    /**
     * @return array<array<mixed>>
     */
    private function readFile(string $filePath, string $format): array
    {
        $rows = [];

        if (ExportFormat::FORMAT_CSV === $format) {
            $reader = new CsvReader(new CsvReaderOptions(FIELD_DELIMITER: ExportFormat::CSV_SEPARATOR));
        } else {
            $reader = new XlsxReader();
        }

        $reader->open($filePath);
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowValues = [];
                foreach ($row->cells as $cell) {
                    $value = $cell->getValue();
                    $rowValues[] = $value instanceof \DateTimeInterface ? $value->format('d/m/Y') : $value;
                }
                $rows[] = $rowValues;
            }
            break;
        }
        $reader->close();

        return $rows;
    }
}
