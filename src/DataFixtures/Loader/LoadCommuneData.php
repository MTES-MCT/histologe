<?php

namespace App\DataFixtures\Loader;

use App\Factory\CommuneFactory;
use App\Manager\CommuneManager;
use App\Manager\TerritoryManager;
use App\Service\Import\CsvParser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class LoadCommuneData extends Fixture implements OrderedFixtureInterface
{
    // File found here: https://www.data.gouv.fr/fr/datasets/codes-postaux/
    private const COMMUNE_LIST_CSV_PATH = '/../Files/codespostaux.csv';

    private const INDEX_CSV_CODE_POSTAL = 0;
    private const INDEX_CSV_CODE_COMMUNE = 1;
    private const INDEX_CSV_NOM_COMMUNE = 2;

    private const FLUSH_COUNT = 1000;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TerritoryManager $territoryManager,
        private CommuneFactory $communeFactory,
        private CommuneManager $communeManager,
        private CsvParser $csvParser,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $totalRead = 0;
        $existingInseeCode = [];
        $territory = null;

        $csvData = $this->csvParser->parse(__DIR__.self::COMMUNE_LIST_CSV_PATH);

        // Start reading
        foreach ($csvData as $lineNumber => $rowData) {
            ++$totalRead;

            if (0 === $lineNumber) {
                continue;
            }

            // Not enough data, let's skip
            if (\count($rowData) < 3) {
                continue;
            }

            $itemCodeCommune = $rowData[self::INDEX_CSV_CODE_COMMUNE];
            if (!empty($existingInseeCode[$itemCodeCommune])) {
                continue;
            }

            $itemCodePostal = $rowData[self::INDEX_CSV_CODE_POSTAL];
            $itemNomCommune = $rowData[self::INDEX_CSV_NOM_COMMUNE];

            $zipCode = self::getZipCodeByCodeCommune($itemCodeCommune);

            if (null === $territory || $zipCode != $territory->getZip()) {
                $territory = $this->territoryManager->findOneBy(['zip' => $zipCode]);
            }

            $commune = $this->communeFactory->createInstanceFrom($territory, $itemNomCommune, $itemCodePostal, $itemCodeCommune);
            $existingInseeCode[$itemCodeCommune] = 1;

            if (0 === $totalRead % self::FLUSH_COUNT) {
                $this->communeManager->save($commune);
            } else {
                $this->communeManager->save($commune, false);
            }
        }

        // Last flush for remaining communes
        $this->communeManager->flush();
    }

    public static function getZipCodeByCodeCommune($itemCodeCommune)
    {
        $codeCommune = $itemCodeCommune;
        $codeCommune = str_pad($codeCommune, 5, '0', \STR_PAD_LEFT);
        $zipCode = substr($codeCommune, 0, 2);
        if ('97' == $zipCode) {
            $zipCode = substr($codeCommune, 0, 3);
        }

        return $zipCode;
    }

    public function getOrder(): int
    {
        return 12;
    }
}
