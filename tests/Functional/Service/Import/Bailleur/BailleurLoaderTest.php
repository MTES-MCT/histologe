<?php

namespace App\Tests\Functional\Service\Import\Bailleur;

use App\Repository\BailleurRepository;
use App\Repository\BailleurTerritoryRepository;
use App\Repository\TerritoryRepository;
use App\Service\Import\Bailleur\BailleurLoader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BailleurLoaderTest extends KernelTestCase
{
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
                'Département du logement attribué' => 'Bouches-du-Rhône',
                "Organisme de l'attribution" => 'MEDITERRANEE 1',
            ],
            [
                'Département du logement attribué' => 'Bouches-du-Rhône',
                "Organisme de l'attribution" => 'MEDITERRANEE 2',
            ],
            [
                'Département du logement attribué' => 'Bouches-du-Rhône',
                "Organisme de l'attribution" => 'MEDITERRANEE 3',
            ],
            [
                'Département du logement attribué' => 'Bouches-du-Rhône',
                "Organisme de l'attribution" => '',
            ],
            [
                'Département du logement attribué' => 'Charente',
                "Organisme de l'attribution" => 'DOMOFRANCE 1',
            ],
            [
                'Département du logement attribué' => 'Charente',
                "Organisme de l'attribution" => 'DOMOFRANCE 2',
            ],
            [
                'Département du logement attribué' => 'Charente',
                "Organisme de l'attribution" => 'MEDITERRANEE 3',
            ],
            [
                'Département du logement attribué' => 'Charente',
                "Organisme de l'attribution" => '',
            ],
            [
                'Département du logement attribué' => 'Saint Martin',
                "Organisme de l'attribution" => 'LOGEMENT HLS',
            ],
        ];
    }
}
