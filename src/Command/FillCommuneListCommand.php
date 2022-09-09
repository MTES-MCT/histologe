<?php

namespace App\Command;

use App\Factory\CommuneFactory;
use App\Manager\CommuneManager;
use App\Manager\TerritoryManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TerritoryManager $territoryManager,
        private CommuneFactory $communeFactory,
        private CommuneManager $communeManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $i = 0;
        $io = new SymfonyStyle($input, $output);

        $fileStream = fopen(self::COMMUNE_LIST_CSV_URL, 'r');
        if (!$fileStream) {
            return Command::FAILURE;
        }

        $existingInseeCode = [];
        $territory = null;

        while (($data = fgetcsv($fileStream, 500)) !== false) {
            $itemCodePostal = $data[self::INDEX_CSV_CODE_POSTAL];
            // Skip first line
            if ('codePostal' === $itemCodePostal) {
                continue;
            }
            $itemCodeCommune = $data[self::INDEX_CSV_CODE_COMMUNE];
            $itemNomCommune = $data[self::INDEX_CSV_NOM_COMMUNE];

            // Commune has already been inserted, let's skip
            if (!empty($existingInseeCode[$itemCodeCommune])) {
                continue;
            }

            // Find the zip code as filled in Territory table
            $codePostal = $itemCodePostal;
            $codePostal = str_pad($codePostal, 5, '0', \STR_PAD_LEFT);
            $zipCode = substr($codePostal, 0, 2);
            if ('97' == $zipCode) {
                $zipCode = substr($codePostal, 0, 3);
            }

            // Skip querying for Territory if same zip code
            if (null === $territory || $zipCode != $territory->getZip()) {
                $territory = $this->territoryManager->findOneBy(['zip' => $zipCode]);
            }

            $commune = $this->communeFactory->createInstanceFrom($territory, $itemNomCommune, $itemCodePostal, $itemCodeCommune);
            $this->communeManager->save($commune);
            $existingInseeCode[$itemCodeCommune] = 1;

            ++$i;
        }

        fclose($fileStream);

        $io->success($i.' communes créées');

        return Command::SUCCESS;
    }
}
