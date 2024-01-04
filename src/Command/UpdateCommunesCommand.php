<?php

namespace App\Command;

use App\DataFixtures\Loader\LoadCommuneData;
use App\Entity\Commune;
use App\Entity\Territory;
use App\Factory\CommuneFactory;
use App\Service\Import\CsvParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[
    AsCommand(
        name: 'app:update-communes',
        description: 'Update communes from csv file'
    )
]
class UpdateCommunesCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly EntityManagerInterface $entityManager,
        private readonly CsvParser $csvParser,
        private readonly CommuneFactory $communeFactory,
        private array $territories = [],
        private array $communes = [],
        private array $csvData = []
    ) {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $list = $this->entityManager->getRepository(Commune::class)->findAll();
        foreach ($list as $commune) {
            $this->communes[$commune->getCodePostal().'-'.$commune->getCodeInsee()] = $commune;
        }
        $list = $this->entityManager->getRepository(Territory::class)->findAll();
        foreach ($list as $territory) {
            $this->territories[$territory->getZip()] = $territory;
        }
        $this->csvData = $this->csvParser->parse($this->params->get('kernel.project_dir').LoadCommuneData::COMMUNE_LIST_CSV_PATH);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nbUpdate = 0;
        $nbInsert = 0;
        $nbDelete = 0;

        foreach ($this->csvData as $rowData) {
            $itemCodeCommune = $rowData[LoadCommuneData::INDEX_CSV_CODE_COMMUNE];
            $itemCodePostal = $rowData[LoadCommuneData::INDEX_CSV_CODE_POSTAL];
            $itemNomCommune = $rowData[LoadCommuneData::INDEX_CSV_NOM_COMMUNE];

            $keyCommune = $itemCodePostal.'-'.$itemCodeCommune;
            if (isset($this->communes[$keyCommune])) {
                if ($this->communes[$keyCommune]->getNom() != $itemNomCommune) {
                    $this->communes[$keyCommune]->setNom($itemNomCommune);
                    ++$nbUpdate;
                }
                unset($this->communes[$keyCommune]);
                continue;
            }

            $zipCode = LoadCommuneData::getZipCodeByCodeCommune($itemCodeCommune);
            $new = $this->communeFactory->createInstanceFrom(territory: $this->territories[$zipCode], nom: $itemNomCommune, codePostal: $itemCodePostal, codeInsee: $itemCodeCommune);
            $this->entityManager->persist($new);
            ++$nbInsert;
            unset($this->communes[$keyCommune]);
        }

        foreach ($this->communes as $commune) {
            $this->entityManager->remove($commune);
            ++$nbDelete;
        }

        $this->entityManager->flush();

        $this->io->success(sprintf('Inserted %d communes', $nbInsert));
        $this->io->success(sprintf('Updated %d communes', $nbUpdate));
        $this->io->success(sprintf('Deleted %d communes', $nbDelete));

        return Command::SUCCESS;
    }
}
