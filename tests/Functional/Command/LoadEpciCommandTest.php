<?php

namespace App\Tests\Functional\Command;

use App\Command\LoadEpciCommand;
use App\Repository\CommuneRepository;
use App\Repository\EpciRepository;
use App\Repository\TerritoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class LoadEpciCommandTest extends KernelTestCase
{
    public function testLoadWithSuccess(): void
    {
        self::bootKernel();
        $responses = [
            new MockResponse($this->getEpciAllResponse()),
            new MockResponse($this->getEpciErdreGesvresCommunes()),
            new MockResponse($this->getEpciPaysAncenis()),
            new MockResponse($this->getEpciAixMarseilleProvence()),
        ];
        $mockHttpClient = new MockHttpClient($responses);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $command = new LoadEpciCommand(
            $mockHttpClient,
            self::getContainer()->get(TerritoryRepository::class),
            self::getContainer()->get(CommuneRepository::class),
            self::getContainer()->get(EpciRepository::class),
            $entityManager
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('EPCI loaded with 25 communes that belong to EPCI', $output, $output);
        $this->assertStringContainsString(
            '35703 communes code postal might be obsolete.',
            $output,
            $output);
    }

    private function getEpciAllResponse(): string
    {
        return json_encode([
            [
                'nom' => 'CC d\'Erdre et Gesvres',
                'code' => '244400503',
            ],
            [
                'nom' => 'CC du Pays d\'Ancenis',
                'code' => '244400552',
            ],
            [
                'nom' => 'Métropole d\'Aix-Marseille-Provence',
                'code' => '200054807',
            ],
        ]);
    }

    private function getEpciErdreGesvresCommunes(): string
    {
        return json_encode([
            ['nom' => 'Petit-Mars', 'codesPostaux' => ['44390'], 'code' => '44122'],
            ['nom' => 'Saint-Mars-du-Désert', 'codesPostaux' => ['44850'], 'code' => '44179'],
        ]);
    }

    private function getEpciPaysAncenis(): string
    {
        return json_encode([
                ['nom' => 'Le Cellier', 'codesPostaux' => ['44850'], 'code' => '44028'],
                ['nom' => 'Ligné', 'codesPostaux' => ['44850'], 'code' => '44082'],
        ]);
    }

    private function getEpciAixMarseilleProvence(): string
    {
        return json_encode([
            [
                'nom' => 'Marseille',
                'code' => '13055',
                'codesPostaux' => [
                    '13001',
                    '13002',
                    '13003',
                    '13004',
                    '13005',
                    '13006',
                    '13007',
                    '13008',
                    '13009',
                    '13010',
                    '13011',
                    '13012',
                    '13013',
                    '13014',
                    '13015',
                    '13016',
                ],
            ],
            [
                'nom' => 'Aix-en-Provence',
                'code' => '13001',
                'codesPostaux' => [
                    '13080',
                    '13090',
                    '13100',
                    '13290',
                    '13540',
                ],
            ],
        ]);
    }
}
