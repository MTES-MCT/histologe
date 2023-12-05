<?php

namespace App\Service\Import\Desordres;

use App\Entity\DesordreCategorie;
use App\Entity\DesordreCritere;
use App\Entity\DesordrePrecision;
use App\Manager\DesordreCategorieManager;
use App\Manager\DesordreCritereManager;
use App\Manager\DesordrePrecisionManager;
use App\Manager\ManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class DesordresTablesLoader
{
    private const FLUSH_COUNT = 200;

    private array $metadata = [
        'count_desordre_categorie_created' => 0,
        'count_desordre_critere_created' => 0,
        'count_desordre_precision_created' => 0,
        'count_desordre_precision_updated' => 0,
    ];

    public function __construct(
        private readonly ManagerInterface $manager,
        private readonly DesordreCritereManager $desordreCritereManager,
        private readonly DesordreCategorieManager $desordreCategorieManager,
        private readonly DesordrePrecisionManager $desordrePrecisionManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function load(array $data, ?OutputInterface $output = null): void
    {
        $countRow = 0;
        if ($output) {
            $progressBar = new ProgressBar($output);
            $progressBar->start(\count($data));
        }

        foreach ($data as $item) {
            if (\count($item) > 1) {
                if ($output) {
                    $progressBar->advance();
                }
                $desordreCategorie = $this->createDesordreCategorie($item[DesordresTablesHeader::CATEGORIE_LABEL_BO]);

                $desordreCritere = $this->createDesordreCritere($item, $desordreCategorie);

                $this->createDesordrePrecision($item, $desordreCritere);
            }
            if (0 === $countRow % self::FLUSH_COUNT) {
                $this->logger->info(sprintf('in progress - %s rows treated', $countRow));
                $this->manager->flush();
            }
        }

        $this->manager->flush();
        if ($output) {
            $progressBar->finish();
        }
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    private function createDesordreCategorie(string $labelCategorie): DesordreCategorie
    {
        $desordreCategorie = $this->desordreCategorieManager->findOneBy(['label' => $labelCategorie]);
        if (null === $desordreCategorie) {
            $desordreCategorie = $this->desordreCategorieManager->createOrUpdate($labelCategorie);
            ++$this->metadata['count_desordre_categorie_created'];
        }

        return $desordreCategorie;
    }

    private function createDesordreCritere(array $item, DesordreCategorie $desordreCategorie): DesordreCritere
    {
        $desordreCritere = $this->desordreCritereManager->findOneBy(
            ['slugCritere' => $item[DesordresTablesHeader::CRITERE_SLUG]]
        );
        $data = [];
        $data['slugCategorie'] = $item[DesordresTablesHeader::CATEGORIE_SLUG];
        $data['labelCategorie'] = $item[DesordresTablesHeader::CATEGORIE_LABEL];
        $data['zoneCategorie'] = $item[DesordresTablesHeader::CATEGORIE_ZONE];
        $data['labelCritere'] = $item[DesordresTablesHeader::CRITERE_LABEL] ?? null;
        $data['desordreCategorie'] = $desordreCategorie;

        if (null === $desordreCritere) {
            ++$this->metadata['count_desordre_critere_created'];
        }

        return $this->desordreCritereManager->createOrUpdate($item[DesordresTablesHeader::CRITERE_SLUG], $data);
    }

    private function createDesordrePrecision(array $item, DesordreCritere $desordreCritere): DesordrePrecision
    {
        $slugPrecision = '' !== $item[DesordresTablesHeader::PRECISION_SLUG] ?
        $item[DesordresTablesHeader::PRECISION_SLUG] :
        $item[DesordresTablesHeader::CRITERE_SLUG];

        /** @var DesordrePrecision $desordrePrecision */
        $desordrePrecision = $this->desordrePrecisionManager->findOneBy(
            ['desordrePrecisionSlug' => $slugPrecision]
        );
        $data = [];
        $data['coef'] = $item[DesordresTablesHeader::PRECISION_COEFF];
        $data['danger'] = $item[DesordresTablesHeader::PRECISION_DANGER];
        $data['suroccupation'] = $item[DesordresTablesHeader::PRECISION_SUROCCUPATION];
        $data['label'] = $item[DesordresTablesHeader::PRECISION_CONDITION].' - '
        .$item[DesordresTablesHeader::PRECISION_PIECE];
        $data['procedure'] = $item[DesordresTablesHeader::PRECISION_PROCEDURES];
        $data['desordreCritere'] = $desordreCritere;

        if (null === $desordrePrecision) {
            ++$this->metadata['count_desordre_precision_created'];
        } else {
            ++$this->metadata['count_desordre_precision_updated'];
        }

        return $this->desordrePrecisionManager->createOrUpdate($slugPrecision, $data);
    }
}
