<?php

namespace App\Tests\Functional\Service\Import\Bailleur;

use App\Repository\BailleurRepository;
use App\Repository\BailleurTerritoryRepository;
use App\Repository\TerritoryRepository;
use App\Service\Import\Bailleur\BailleurHeader;
use App\Service\Import\Bailleur\BailleurLoader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BailleurLoaderTest extends KernelTestCase
{
    private const BOUCHE_DU_RHONE = '13';
    private const CHARENTE = '16';

    public function testLoadValidBailleur(): void
    {
        $bailleurLoader = new BailleurLoader(
            self::getContainer()->get(BailleurRepository::class),
            self::getContainer()->get(BailleurTerritoryRepository::class),
            self::getContainer()->get(TerritoryRepository::class),
            self::getContainer()->get(EntityManagerInterface::class)
        );

        $bailleurLoader->load($this->getData());

        $metadata = $bailleurLoader->getMetadata();
        $this->assertEquals(4, $metadata['new_bailleurs']);
        $this->assertEquals(1, $metadata['updated_bailleurs']);
        $this->assertStringContainsString('Le nom bailleur est vide.', $metadata['errors'][0]);
        $this->assertStringContainsString('Le territoire n\'existe pas.', $metadata['errors'][1]);
    }

    private function getData(): array
    {
        return [
            [
                BailleurHeader::DEPARTEMENT => self::BOUCHE_DU_RHONE,
                BailleurHeader::ENSEIGNE => '13 HABITAT',
                BailleurHeader::RAISON_SOCIALE => 'OPH 13 HABITAT',
                BailleurHeader::SIRET => '00000000000001',
            ],
            [
                BailleurHeader::DEPARTEMENT => self::BOUCHE_DU_RHONE,
                BailleurHeader::ENSEIGNE => 'MEDITERRANEE 2',
                BailleurHeader::RAISON_SOCIALE => 'OPH MEDITERRANEE 2',
                BailleurHeader::SIRET => '00000000000002',
            ],
            [
                BailleurHeader::DEPARTEMENT => self::BOUCHE_DU_RHONE,
                BailleurHeader::ENSEIGNE => 'MEDITERRANEE 3',
                BailleurHeader::RAISON_SOCIALE => 'OPH MEDITERRANEE 3',
                BailleurHeader::SIRET => '00000000000003',
            ],
            [
                BailleurHeader::DEPARTEMENT => self::BOUCHE_DU_RHONE,
                BailleurHeader::ENSEIGNE => '',
                BailleurHeader::RAISON_SOCIALE => '',
                BailleurHeader::SIRET => '',
            ],
            [
                BailleurHeader::DEPARTEMENT => self::CHARENTE,
                BailleurHeader::ENSEIGNE => 'DOMOFRANCE 1',
                BailleurHeader::RAISON_SOCIALE => 'OPH DOMOFRANCE 1',
                BailleurHeader::SIRET => '00000000000004',
            ],
            [
                BailleurHeader::DEPARTEMENT => self::CHARENTE,
                BailleurHeader::ENSEIGNE => 'DOMOFRANCE 2',
                BailleurHeader::RAISON_SOCIALE => 'OPH DOMOFRANCE 2',
                BailleurHeader::SIRET => '00000000000005',
            ],
            [
                BailleurHeader::DEPARTEMENT => self::CHARENTE,
                BailleurHeader::ENSEIGNE => 'MEDITERRANEE 3',
                BailleurHeader::RAISON_SOCIALE => 'OPH MEDITERRANEE 3',
                BailleurHeader::SIRET => '00000000000003',
            ],
            [
                BailleurHeader::DEPARTEMENT => '458',
                BailleurHeader::ENSEIGNE => 'LOGEMENT HLS',
                BailleurHeader::RAISON_SOCIALE => '',
                BailleurHeader::SIRET => '00000000000006',
            ],
        ];
    }
}
