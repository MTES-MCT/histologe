<?php

namespace App\Service\Import\Bailleur;

use App\Entity\Bailleur;
use App\Repository\BailleurRepository;
use App\Repository\TerritoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class BailleurLoader
{
    private const FLUSH_COUNT = 500;

    private array $metadata = [
        'count_bailleurs' => 0,
        'errors' => [],
    ];

    public function __construct(
        private BailleurRepository $bailleurRepository,
        private TerritoryRepository $territoryRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function load(array $data, ?OutputInterface $output = null): void
    {
        $progressBar = new ProgressBar($output, \count($data));
        $progressBar->start();
        foreach ($data as $key => $item) {
            if (\count($item) > 1) {
                if (null !== $territoryName = $item[BailleurHeader::DEPARTEMENT]) {
                    $territory = $this->territoryRepository->findOneBy([
                        'name' => $territoryName,
                    ]);
                    if (null !== $territory && !empty($item[BailleurHeader::ORGANISME_NOM])) {
                        $bailleur = $this->bailleurRepository->findOneBy([
                            'name' => $item[BailleurHeader::ORGANISME_NOM],
                            'territory' => $territory->getId(),
                        ]);
                        if (null === $bailleur) {
                            $bailleur = (new Bailleur())
                                ->setTerritory($territory)
                                ->setName($item[BailleurHeader::ORGANISME_NOM])
                                ->setIsSocial(true);
                            $this->entityManager->persist($bailleur);
                            ++$this->metadata['count_bailleurs'];
                        }
                        if (0 === $this->metadata['count_bailleurs'] % self::FLUSH_COUNT) {
                            $this->entityManager->flush();
                        }
                    } elseif (null === $territory) {
                        $this->metadata['errors'][] = sprintf(
                            '[%s] ligne %d - Le territoire n\'existe pas.',
                            $territoryName,
                            $key
                        );
                    } else {
                        $this->metadata['errors'][] = sprintf(
                            '[%s] ligne %d - Le nom de l\'organisme est manquant.',
                            $territoryName,
                            $key
                        );
                    }
                    if (null !== $output) {
                        $progressBar->advance();
                    }
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
}
