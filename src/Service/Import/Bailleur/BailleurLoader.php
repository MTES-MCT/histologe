<?php

namespace App\Service\Import\Bailleur;

use App\Entity\Bailleur;
use App\Entity\Territory;
use App\Repository\BailleurRepository;
use App\Repository\BailleurTerritoryRepository;
use App\Repository\TerritoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class BailleurLoader
{
    private array $metadata = [
        'count_bailleurs' => 0,
        'errors' => [],
    ];

    public function __construct(
        private BailleurRepository $bailleurRepository,
        private BailleurTerritoryRepository $bailleurTerritoryRepository,
        private TerritoryRepository $territoryRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function load(array $data, ?OutputInterface $output = null): void
    {
        if (null !== $output) {
            $progressBar = new ProgressBar($output, \count($data));
            $progressBar->start();
        }

        foreach ($data as $key => $item) {
            if (\count($item) > 1) {
                if (null !== $territoryName = $item[BailleurHeader::DEPARTEMENT]) {
                    $territory = $this->territoryRepository->findOneBy(['name' => $territoryName]);
                    if ($this->hasErrors($territoryName, $item, $key, $territory)) {
                        continue;
                    }

                    if (!empty($item[BailleurHeader::ORGANISME_NOM])) {
                        $bailleur = $this->createOrUpdateBailleur($item[BailleurHeader::ORGANISME_NOM], $territory);
                        $this->entityManager->persist($bailleur);
                    } else {
                        $this->entityManager->flush();
                    }
                }
                if (null !== $output) {
                    $progressBar->advance();
                }
            }
        }
        if (null !== $output) {
            $progressBar->finish();
            $progressBar->clear();
        }
        $this->entityManager->flush();
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    private function createOrUpdateBailleur(string $bailleurName, Territory $territory): Bailleur
    {
        $bailleur = $this->bailleurRepository->findOneBy(['name' => $bailleurName]);
        $bailleurTerritory = $this->bailleurTerritoryRepository->findOneBy([
            'bailleur' => $bailleur,
            'territory' => $territory,
        ]);
        if (null === $bailleur) {
            $bailleur = (new Bailleur())
                ->addTerritory($territory)
                ->setName($bailleurName);
            ++$this->metadata['count_bailleurs'];
        } elseif (null === $bailleurTerritory) {
            $bailleur->addTerritory($territory);
        }

        return $bailleur;
    }

    private function hasErrors(string $territoryName, array $item, int $key, ?Territory $territory = null): bool
    {
        if (null === $territory) {
            $this->metadata['errors'][] = sprintf(
                '[%s] ligne %d - Le territoire n\'existe pas.',
                $territoryName,
                $key + 2);

            return true;
        }

        return false;
    }
}
