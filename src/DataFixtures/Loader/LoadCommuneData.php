<?php

namespace App\DataFixtures\Loader;

use App\Factory\CommuneFactory;
use App\Manager\CommuneManager;
use App\Manager\TerritoryManager;
use App\Service\Import\CsvParser;
use App\Utils\ImportCommune;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LoadCommuneData extends Fixture implements OrderedFixtureInterface
{
    private const FLUSH_COUNT = 1000;
    private const ZIP_CODES_ALLOWED = ['01', '06', '13', '44', '30', '62', '64', '67', '69', '89', '93'];

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly TerritoryManager $territoryManager,
        private readonly CommuneFactory $communeFactory,
        private readonly CommuneManager $communeManager,
        private readonly CsvParser $csvParser,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $totalRead = 0;
        $existingCpAndInseeCode = [];
        $territory = null;

        $csvData = $this->csvParser->parse($this->params->get('kernel.project_dir').ImportCommune::COMMUNE_LIST_CSV_PATH);

        // Start reading
        foreach ($csvData as $rowData) {
            ++$totalRead;

            // Not enough data, let's skip
            if (\count($rowData) < 3) {
                continue;
            }

            $itemCodeCommune = $rowData[ImportCommune::INDEX_CSV_CODE_COMMUNE];
            $itemCodePostal = $rowData[ImportCommune::INDEX_CSV_CODE_POSTAL];
            $itemNomCommune = $rowData[ImportCommune::INDEX_CSV_NOM_COMMUNE];

            $keyCommune = $itemCodePostal.'-'.$itemCodeCommune;
            if (!empty($existingCpAndInseeCode[$keyCommune])) {
                continue;
            }

            $zipCode = ImportCommune::getZipCodeByCodeCommune($itemCodeCommune);

            if (array_filter(self::ZIP_CODES_ALLOWED, fn ($zipCodeAllowed) => $zipCode === $zipCodeAllowed)) {
                if (null === $territory || $zipCode != $territory->getZip()) {
                    $territory = $this->territoryManager->findOneBy(['zip' => $zipCode]);
                }

                $commune = $this->communeFactory->createInstanceFrom(
                    $territory,
                    $itemNomCommune,
                    $itemCodePostal,
                    $itemCodeCommune
                );

                $existingCpAndInseeCode[$keyCommune] = 1;

                if (0 === $totalRead % self::FLUSH_COUNT) {
                    $this->communeManager->save($commune);
                } else {
                    $this->communeManager->save($commune, false);
                }
            }
        }

        // Last flush for remaining communes
        $this->communeManager->flush();
    }

    public function getOrder(): int
    {
        return 17;
    }
}
