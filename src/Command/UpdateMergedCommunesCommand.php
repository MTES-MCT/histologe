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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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
     */
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly EntityManagerInterface $entityManager,
        private readonly CsvParser $csvParser,
        private readonly CommuneRepository $communeRepository,
        private array $csvData = [],
        private array $renamedCommunes = [],
    ) {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        $csvUrl = $this->parameterBag->get('new_communes_csv_url');
        if (empty($csvUrl)) {
            throw new \RuntimeException('The parameter "new_communes_csv_url" is not defined or is empty.');
        }

        $this->csvData = $this->csvParser->parse($csvUrl);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nbDeprecated = 0;
        $nbRenamed = 0;

        // Ajout d'une barre de progression
        $progressBar = $this->io->createProgressBar(count($this->csvData));
        $progressBar->start();

        foreach ($this->csvData as $rowData) {
            $progressBar->advance();
            $result = $this->processCsvRow($rowData);
            $nbDeprecated += $result['deprecated'];
            $nbRenamed += $result['renamed'];
        }

        $progressBar->finish();
        $this->entityManager->flush();

        $this->io->success(\sprintf('Deprecated %d communes', $nbDeprecated));
        $this->io->success(\sprintf('Renamed %d communes', $nbRenamed));

        return Command::SUCCESS;
    }

    /**
     * @param array<mixed> $rowData
     *
     * @return array{deprecated: int, renamed: int}
     */
    public function processCsvRow(array $rowData): array
    {
        $nbDeprecated = 0;
        $nbRenamed = 0;

        // NB : On n'a pas les codes postaux dans le csv, donc on va garder par défaut les données des anciennes communes
        $newCommuneInsee = $this->cleanInseeCode($rowData[self::INDEX_CSV_NEW_COMMUNE_INSEE]);
        $newCommuneName = $this->cleanCommuneName($rowData[self::INDEX_CSV_NEW_COMMUNE_NAME]);
        $oldCommuneInsee = $this->cleanInseeCode($rowData[self::INDEX_CSV_OLD_COMMUNE_INSEE]);

        // Si un des champs n'est pas rempli, on passe à la suite
        if (!$newCommuneInsee || !$newCommuneName || !$oldCommuneInsee) {
            return ['deprecated' => $nbDeprecated, 'renamed' => $nbRenamed];
        }

        // On recherche les items de commune qui correspondent au code Insee de l'ancienne commune (il peut y en avoir plusieurs car une commune a un code Insee mais potentiellement plusieurs codes postaux)
        $communesWithOldInsee = $this->communeRepository->findBy(['codeInsee' => $oldCommuneInsee]);
        foreach ($communesWithOldInsee as $commune) {
            // Si le nom de l'ancienne commune est différent du nom de la nouvelle commune, on lie l'ancienne à la nouvelle commune
            if ($commune->getNom() !== $newCommuneName && !$commune->getCommuneMergedInto()) {
                // On cherche d'abord dans les communes renommées (cache), puis en base
                $newCommune = $this->renamedCommunes[$newCommuneInsee] ?? $this->communeRepository->findOneBy(['codeInsee' => $newCommuneInsee, 'nom' => $newCommuneName]);

                if (!$newCommune) {
                    $this->io->warning(\sprintf('No commune found with code Insee %s and name %s, skipping deprecation of commune %d', $newCommuneInsee, $newCommuneName, $commune->getId()));
                    continue;
                }

                $this->io->info(\sprintf('Deprecates commune %d : %s, merged in %s', $commune->getId(), $commune->getNom(), $newCommuneName));
                $commune->setCommuneMergedInto($newCommune);
                $this->entityManager->persist($commune);
                ++$nbDeprecated;
            }
        }

        // On recherche les items de communes qui existent déjà avec le nouveau code insee mais qui n'ont pas le même nom
        // il y a une contrainte d'unicité sur le couple code postal / code insee, donc on est obligé de renommer, et pas marquer comme déprécié
        $communesWithNewInsee = $this->communeRepository->findBy(['codeInsee' => $newCommuneInsee]);
        foreach ($communesWithNewInsee as $commune) {
            if ($commune->getNom() !== $newCommuneName) {
                $this->io->info(\sprintf('Renames commune %d : %s to %s', $commune->getId(), $commune->getNom(), $newCommuneName));
                $commune->setNom($newCommuneName);
                $this->renamedCommunes[$commune->getCodeInsee()] = $commune;
                $this->entityManager->persist($commune);
                ++$nbRenamed;
            }
        }

        // On ne peut pas insérer si on n'a pas trouvé d'équivalent avec le nouveau code Insee, car on n'aura pas de code postal à mettre en correspondance

        return ['deprecated' => $nbDeprecated, 'renamed' => $nbRenamed];
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
