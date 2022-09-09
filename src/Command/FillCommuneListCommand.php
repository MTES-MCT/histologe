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
    private const COMMUNE_LIST_JSON_URL = 'https://unpkg.com/codes-postaux@3.6.0/codes-postaux.json';

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

        $fileResult = file_get_contents(self::COMMUNE_LIST_JSON_URL);
        $listCommunes = json_decode($fileResult);

        $existingInseeCode = [];
        $territory = null;

        foreach ($listCommunes as $itemCommune) {
            // Commune has already been inserted, let's skip
            if (!empty($existingInseeCode[$itemCommune->codeCommune])) {
                continue;
            }

            // Find the zip code as filled in Territory table
            $codePostal = $itemCommune->codePostal;
            $codePostal = str_pad($codePostal, 5, '0', \STR_PAD_LEFT);
            $zipCode = substr($codePostal, 0, 2);
            if ('97' == $zipCode) {
                $zipCode = substr($codePostal, 0, 3);
            }

            // Skip querying for Territory if same zip code
            if (null === $territory || $zipCode != $territory->getZip()) {
                $territory = $this->territoryManager->findOneBy(['zip' => $zipCode]);
            }

            $commune = $this->communeFactory->createInstanceFrom($territory, $itemCommune->nomCommune, $itemCommune->codePostal, $itemCommune->codeCommune);
            $this->communeManager->save($commune);
            $existingInseeCode[$itemCommune->codeCommune] = 1;

            ++$i;
        }

        $io->success($i.' communes créées');

        return Command::SUCCESS;
    }
}
