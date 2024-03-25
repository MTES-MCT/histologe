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
    private const BOUCHE_DU_RHONE = 'Bouches-du-Rhône';
    private const CHARENTE = 'Charente';

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
        $this->assertEquals(5, $metadata['count_bailleurs']);
        $this->assertStringContainsString('Le territoire n\'existe pas.', $metadata['errors'][0]);
    }

    private function getData(): array
    {
        return [
            [
                BailleurHeader::DEPARTEMENT => self::BOUCHE_DU_RHONE,
                BailleurHeader::ORGANISME_NOM => 'MEDITERRANEE 1',
            ],
            [
                BailleurHeader::DEPARTEMENT => self::BOUCHE_DU_RHONE,
                BailleurHeader::ORGANISME_NOM => 'MEDITERRANEE 2',
            ],
            [
                BailleurHeader::DEPARTEMENT => self::BOUCHE_DU_RHONE,
                BailleurHeader::ORGANISME_NOM => 'MEDITERRANEE 3',
            ],
            [
                BailleurHeader::DEPARTEMENT => self::BOUCHE_DU_RHONE,
                BailleurHeader::ORGANISME_NOM => '',
            ],
            [
                BailleurHeader::DEPARTEMENT => self::CHARENTE,
                BailleurHeader::ORGANISME_NOM => 'DOMOFRANCE 1',
            ],
            [
                BailleurHeader::DEPARTEMENT => self::CHARENTE,
                BailleurHeader::ORGANISME_NOM => 'DOMOFRANCE 2',
            ],
            [
                BailleurHeader::DEPARTEMENT => self::CHARENTE,
                BailleurHeader::ORGANISME_NOM => 'MEDITERRANEE 3',
            ],
            [
                BailleurHeader::DEPARTEMENT => self::CHARENTE,
                BailleurHeader::ORGANISME_NOM => '',
            ],
            [
                BailleurHeader::DEPARTEMENT => 'Saint Martin',
                BailleurHeader::ORGANISME_NOM => 'LOGEMENT HLS',
            ],
        ];
    }
}
