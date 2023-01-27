<?php

namespace App\Command;

use App\Factory\CommuneFactory;
use App\Manager\CommuneManager;
use App\Manager\TerritoryManager;
use App\Service\Import\CsvParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:commune-filler',
    description: 'Fill the Commune table with real communes from external json',
)]
class FillCommuneListCommand extends Command
{
    // File found here: https://www.data.gouv.fr/fr/datasets/codes-postaux/
    private const COMMUNE_LIST_CSV_URL = 'https://www.data.gouv.fr/fr/datasets/r/3b318b9e-e11b-4d57-a3e0-8fdc7bfb601a';

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
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // ini_set("memory_limit", "-1"); // Hack for local env: uncomment this line if you have memory limit error

        $totalRead = 0;
        $io = new SymfonyStyle($input, $output);

        $existingInseeCode = [];
        $territory = null;

        $csvData = $this->csvParser->parse(self::COMMUNE_LIST_CSV_URL);

        $progressBar = new ProgressBar($output, \count($csvData));
        $progressBar->start();

        // Start reading
        foreach ($csvData as $lineNumber => $rowData) {
            ++$totalRead;
            $progressBar->advance();

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

        $progressBar->finish();
        $io->success($totalRead.' lignes traitées');

        return Command::SUCCESS;
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
}
