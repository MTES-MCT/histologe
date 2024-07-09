<?php

namespace App\Service\Import\Bailleur;

use App\Entity\Bailleur;
use App\Repository\BailleurRepository;
use App\Repository\BailleurTerritoryRepository;
use App\Repository\TerritoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class BailleurLoader
{
    private array $territories = [];
    private array $bailleurs = [];

    private array $metadata = [
        'new_bailleurs' => 0,
        'updated_bailleurs' => 0,
        'deleted_bailleurs' => 0,
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
        $this->initData();
        $bailleursCreatedOrUpdated = [];
        if (null !== $output) {
            $progressBar = new ProgressBar($output, \count($data));
            $progressBar->start();
        }

        foreach ($data as $key => $item) {
            if (\count($item) > 1) {
                $deptCode = str_pad($item[BailleurHeader::DEPARTEMENT], 2, '0', \STR_PAD_LEFT);
                $bailleurNom = $item[BailleurHeader::ENSEIGNE];
                $bailleurRaisonSociale = $item[BailleurHeader::RAISON_SOCIALE];
                if (!isset($this->territories[$deptCode])) {
                    $this->metadata['errors'][] = sprintf('[%s] ligne %d - Le territoire n\'existe pas.', $deptCode, $key + 2);
                    continue;
                }
                if (empty($bailleurNom)) {
                    $bailleurNom = $bailleurRaisonSociale;
                }
                if (empty($bailleurNom)) {
                    $this->metadata['errors'][] = sprintf('ligne %d - Le nom bailleur est vide.', $key + 2);
                    continue;
                }
                $isNew = false;
                $baileurNomSanitized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtoupper($bailleurNom));
                $bailleurRaisonSocialeSanitized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtoupper($bailleurRaisonSociale));
                if (isset($this->bailleurs[$baileurNomSanitized])) {
                    $bailleur = $this->bailleurs[$baileurNomSanitized];
                } elseif ($bailleurRaisonSocialeSanitized && isset($this->bailleurs[$bailleurRaisonSocialeSanitized])) {
                    $bailleur = $this->bailleurs[$bailleurRaisonSocialeSanitized];
                } else {
                    $bailleur = new Bailleur();
                    $this->entityManager->persist($bailleur);
                    $isNew = true;
                }
                if (!isset($bailleursCreatedOrUpdated[$bailleurRaisonSociale])) {
                    $bailleursCreatedOrUpdated[$bailleurRaisonSociale] = true;
                    if ($isNew) {
                        ++$this->metadata['new_bailleurs'];
                        $this->bailleurs[$baileurNomSanitized] = $bailleur;
                    } else {
                        ++$this->metadata['updated_bailleurs'];
                    }
                }
                $bailleur->setName($bailleurNom)
                         ->setRaisonSociale($bailleurRaisonSociale)
                         ->setSiret($item[BailleurHeader::SIRET])
                         ->addTerritory($this->territories[$deptCode]);
                if (null !== $output) {
                    $progressBar->advance();
                }
            }
        }
        if (null !== $output) {
            $progressBar->finish();
            $progressBar->clear();
        }
        foreach ($this->bailleurs as $bailleur) {
            if (!$bailleur->getBailleurTerritories()->count()) {
                $this->entityManager->remove($bailleur);
                ++$this->metadata['deleted_bailleurs'];
            }
        }
        $this->entityManager->flush();
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    private function initData(): void
    {
        $this->territories = $this->territoryRepository->findAllIndexedByZip();
        $this->bailleurs = $this->bailleurRepository->findAllIndexedByNameSanitizedWithBailleurTerritories();
        foreach ($this->bailleurs as $bailleur) {
            foreach ($bailleur->getBailleurTerritories() as $bailleurTerritory) {
                $bailleur->removeBailleurTerritory($bailleurTerritory);
                $this->entityManager->remove($bailleurTerritory);
            }
        }
        $this->entityManager->flush();
    }
}
