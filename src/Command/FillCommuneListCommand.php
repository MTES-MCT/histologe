<?php

namespace App\Command;

use App\Factory\CommuneFactory;
use App\Manager\CommuneManager;
use App\Manager\TerritoryManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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

    private const READ_LENGTH = 5000;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TerritoryManager $territoryManager,
        private CommuneFactory $communeFactory,
        private CommuneManager $communeManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('offset', InputArgument::REQUIRED, 'The offset where to start reading the file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nb = 0;
        $io = new SymfonyStyle($input, $output);

        $offset = $input->getArgument('offset');
        if (empty($offset)) {
            $offset = 0;
        }
        $start_from = (int) $offset * self::READ_LENGTH;
        $io->info(sprintf('Insertion will start on line %s', $start_from));

        $fileStream = fopen(self::COMMUNE_LIST_CSV_URL, 'r');
        if (!$fileStream) {
            return Command::FAILURE;
        }

        $existingInseeCode = [];
        $territory = null;

        // Skip first line
        fgets($fileStream);
        // Skip offset
        for ($i = 0; $i < $start_from; ++$i) {
            fgets($fileStream);
        }

        // Start reading
        while (($data = fgetcsv($fileStream, 1000)) !== false) {
            ++$nb;
            if ($nb > self::READ_LENGTH) {
                break;
            }

            // Not enough data, let's skip
            if (\count($data) < 3) {
                continue;
            }
            $itemCodePostal = $data[self::INDEX_CSV_CODE_POSTAL];
            $itemCodeCommune = $data[self::INDEX_CSV_CODE_COMMUNE];
            $itemNomCommune = $data[self::INDEX_CSV_NOM_COMMUNE];

            // Commune has already been inserted, let's skip
            if (!empty($existingInseeCode[$itemCodeCommune])) {
                continue;
            }

            // Find the zip code as filled in Territory table
            $zipCode = $this->getZipCodeByCodeCommune($itemCodeCommune);

            // Query for Territory only if different zip code
            if (null === $territory || $zipCode != $territory->getZip()) {
                $territory = $this->territoryManager->findOneBy(['zip' => $zipCode]);
            }

            $commune = $this->communeFactory->createInstanceFrom($territory, $itemNomCommune, $itemCodePostal, $itemCodeCommune);
            $this->communeManager->save($commune);
            $existingInseeCode[$itemCodeCommune] = 1;
        }

        fclose($fileStream);

        $io->success(($nb - 1).' lignes traitées');

        return Command::SUCCESS;
    }

    private function getZipCodeByCodeCommune($itemCodeCommune)
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
