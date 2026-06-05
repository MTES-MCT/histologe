<?php

namespace App\Command;

use App\Repository\CommuneRepository;
use App\Service\Import\CsvParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:update-merged-communes',
    description: 'Update merged communes from data.gouv csv file'
)]
class UpdateMergedCommunesCommand extends Command
{
    private const INDEX_CSV_NEW_COMMUNE_INSEE = 1;
    private const INDEX_CSV_NEW_COMMUNE_NAME = 2;
    private const INDEX_CSV_OLD_COMMUNE_INSEE = 3;

    private SymfonyStyle $io;

    /**
     * @param array<mixed> $csvData
     * @param array<mixed> $renamedCommunes
     * @param array<mixed> $resetInseeWithCP
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CsvParser $csvParser,
        private readonly CommuneRepository $communeRepository,
        #[Autowire(env: 'NEW_COMMUNES_CSV_URL')]
        private string $newCommunesCsvUrl,
        private array $csvData = [],
        private array $renamedCommunes = [],
        private array $resetInseeWithCP = [],
        private int $nbDeprecated = 0,
        private int $nbRenamed = 0,
    ) {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        if (empty($this->newCommunesCsvUrl)) {
            throw new \RuntimeException('The parameter "NEW_COMMUNES_CSV_URL" is not defined or is empty.');
        }

        $this->csvData = $this->csvParser->parse($this->newCommunesCsvUrl);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->nbDeprecated = 0;
        $this->nbRenamed = 0;

        $progressBar = $this->io->createProgressBar(count($this->csvData));
        $progressBar->start();

        foreach ($this->csvData as $rowData) {
            $progressBar->advance();
            $this->processCsvRow($rowData);
        }

        $progressBar->finish();
        $this->entityManager->flush();

        $this->io->success(\sprintf('Deprecated %d communes', $this->nbDeprecated));
        $this->io->success(\sprintf('Renamed %d communes', $this->nbRenamed));

        return Command::SUCCESS;
    }

    /**
     * @param array<mixed> $rowData
     */
    public function processCsvRow(array $rowData): void
    {
        // NB : On n'a pas les codes postaux dans le csv, donc on va garder par défaut les données des anciennes communes
        $newCommuneInsee = $this->cleanInseeCode($rowData[self::INDEX_CSV_NEW_COMMUNE_INSEE]);
        $newCommuneName = $this->cleanCommuneName($rowData[self::INDEX_CSV_NEW_COMMUNE_NAME]);
        $oldCommuneInsee = $this->cleanInseeCode($rowData[self::INDEX_CSV_OLD_COMMUNE_INSEE]);

        // Si un des champs n'est pas rempli, on passe à la suite
        if (!$newCommuneInsee || !$newCommuneName || !$oldCommuneInsee) {
            return;
        }

        // Si le code Insee de l'ancienne commune est le même que le code Insee de la nouvelle commune, on considère que c'est une simple modification du nom de la commune, et on renomme les communes existantes avec ce code Insee
        // Il y a une contrainte d'unicité sur le couple code postal / code insee, donc on est obligé de renommer, et pas marquer comme déprécié
        if ($oldCommuneInsee === $newCommuneInsee) {
            $communesWithNewInsee = $this->communeRepository->findBy(['codeInsee' => $newCommuneInsee]);
            foreach ($communesWithNewInsee as $commune) {
                if ($commune->getNom() !== $newCommuneName) {
                    $this->io->info(\sprintf('Renames commune %d : %s to %s', $commune->getId(), $commune->getNom(), $newCommuneName));
                    $commune->setNom($newCommuneName);
                    $this->renamedCommunes[$commune->getCodeInsee()] = $commune;
                    $this->entityManager->persist($commune);
                    ++$this->nbRenamed;
                }
            }

        // Si le code Insee de l'ancienne commune est différent du code Insee de la nouvelle commune, on considère que c'est une fusion de communes, et on marque comme déprécié les communes existantes avec le code Insee de l'ancienne commune, en les liant à la nouvelle commune
        } else {
            // On recherche les items de commune qui correspondent au code Insee de l'ancienne commune (il peut y en avoir plusieurs car une commune a un code Insee mais potentiellement plusieurs codes postaux)
            $communesWithOldInsee = $this->communeRepository->findBy(['codeInsee' => $oldCommuneInsee]);
            foreach ($communesWithOldInsee as $commune) {
                // Pas besoin de marquer comme mergée si c'est déjà fait
                if ($commune->getCommuneMergedInto()) {
                    continue;
                }

                // Si le couple nouvel insee + code postal de l'ancienne commune n'existe pas, on choisit de renommer la commune existante avec le nouveau nom et le nouveau code Insee
                // Cas sûrement rare, mais permet d'éviter les erreurs lors du checkTerritory
                $existingCommuneWithNewInseeAndPostalCode = $this->communeRepository->findOneBy(['codeInsee' => $newCommuneInsee, 'codePostal' => $commune->getCodePostal()]);
                if (!$existingCommuneWithNewInseeAndPostalCode && !isset($this->resetInseeWithCP[$newCommuneInsee.'-'.$commune->getCodePostal()])) {
                    $this->io->info(\sprintf('Renames commune %d : %s to %s and change Insee code to %s', $commune->getId(), $commune->getNom(), $newCommuneName, $newCommuneInsee));
                    $commune->setNom($newCommuneName);
                    $commune->setCodeInsee($newCommuneInsee);
                    $this->renamedCommunes[$commune->getCodeInsee()] = $commune;
                    $this->resetInseeWithCP[$newCommuneInsee.'-'.$commune->getCodePostal()] = $commune;
                    $this->entityManager->persist($commune);
                    ++$this->nbRenamed;
                    continue;
                }

                // Sinon, on déprécie l'ancienne commune en la liant à la nouvelle commune
                // On cherche d'abord dans les communes renommées (cache), puis en base
                $newCommune = $this->renamedCommunes[$newCommuneInsee] ?? $this->communeRepository->findOneBy(['codeInsee' => $newCommuneInsee]);

                if (!$newCommune) {
                    $this->io->warning(\sprintf('No commune found with code Insee %s and name %s, skipping deprecation of commune %d', $newCommuneInsee, $newCommuneName, $commune->getId()));
                    continue;
                }

                $this->io->info(\sprintf('Deprecates commune %d : %s, merged in %s', $commune->getId(), $commune->getNom(), $newCommuneName));
                $commune->setCommuneMergedInto($newCommune);
                $this->entityManager->persist($commune);
                ++$this->nbDeprecated;
            }
        }

        // On ne peut pas insérer si on n'a pas trouvé d'équivalent avec le nouveau code Insee, car on n'aura pas de code postal à mettre en correspondance
    }

    private function cleanInseeCode(string $codeInsee): string
    {
        // Si on a un code Insee à 4 chiffres, on ajoute un 0 devant pour avoir un code Insee à 5 chiffres
        if (4 === strlen($codeInsee)) {
            return '0'.$codeInsee;
        }

        return $codeInsee;
    }

    private function cleanCommuneName(string $communeName): string
    {
        // On supprime d'éventuels '*' qui arrivent parfois sur certains noms de communes dans le csv
        $communeName = str_replace('*', '', $communeName);

        // On nettoie les noms de communes en supprimant les éventuels espaces en début et fin de chaîne, et en remplaçant les espaces multiples par un espace simple
        return preg_replace('/\s+/', ' ', trim($communeName));
    }
}
