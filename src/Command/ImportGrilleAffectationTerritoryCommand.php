<?php

namespace App\Command;

use App\Factory\PartnerFactory;
use App\Factory\UserFactory;
use App\Manager\PartnerManager;
use App\Manager\TerritoryManager;
use App\Manager\UserManager;
use App\Service\Parser\CsvParser;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:import-grille-affectation',
    description: 'Import grille affectation based on storage S3',
)]
class ImportGrilleAffectationTerritoryCommand extends Command
{
    public function __construct(
        private FilesystemOperator $fileStorage,
        private ParameterBagInterface $parameterBag,
        private CsvParser $csvParser,
        private EntityManagerInterface $entityManager,
        private UserFactory $userFactory,
        private PartnerFactory $partnerFactory,
        private UserManager $userManager,
        private PartnerManager $partnerManager,
        private TerritoryManager $territoryManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('territory_zip', InputArgument::REQUIRED, 'Territory zip to target');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $territoryZip = $input->getArgument('territory_zip');

        $fromFile = 'csv/grille_affectation_'.$territoryZip.'.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'grille.csv';

        $territory = $this->territoryManager->findOneBy(['zip' => $territoryZip]);
        if (null === $territory) {
            $io->error('Territory does not exists');

            return Command::FAILURE;
        }

        if (!$this->fileStorage->fileExists($fromFile)) {
            $io->error('CSV File does not exists');

            return Command::FAILURE;
        }

        $this->createTmpFileFromBucket($fromFile, $toFile);
        $data = $this->csvParser->parse($toFile);

        $nbUser = 0;
        $nbPartner = 0;
        foreach ($data as $lineNumber => $row) {
            $partner = null;
            if (0 === $lineNumber || $data[$lineNumber][0] !== $data[$lineNumber - 1][0]) {
                $partner = $this->partnerFactory->createInstanceFrom(
                    territory: $territory,
                    name: $row[0],
                    email: !empty($row[3]) ? $row[3] : null,
                    isCommune: !empty($row[1]) ? true : false,
                    insee: !empty($row[1]) ? [$row[2]] : [],
                );
                $this->partnerManager->save($partner, false);
                ++$nbPartner;
            }

            $user = $this->userFactory->createInstanceFrom(
                roleLabel: $row[4],
                territory: $territory,
                partner: $partner,
                firstname: $row[5],
                lastname: $row[6],
                email: $row[7]
            );
            $this->partnerManager->save($user, false);
            ++$nbUser;
        }

        $this->entityManager->flush();
        $io->success($nbPartner.' partner(s) created, '.$nbUser.' user(s) created');

        $territory->setIsActive(true);
        $this->territoryManager->save($territory);

        $io->success($territory->getName().' has been activated');

        return Command::SUCCESS;
    }

    private function createTmpFileFromBucket($from, $to): void
    {
        $resourceBucket = $this->fileStorage->read($from);
        $resourceFileSytem = fopen($to, 'w');
        fwrite($resourceFileSytem, $resourceBucket);
        fclose($resourceFileSytem);
    }
}
