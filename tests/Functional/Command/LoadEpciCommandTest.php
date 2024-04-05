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
use Symfony\Component\String\Slugger\SluggerInterface;

class LoadEpciCommandTest extends KernelTestCase
{
    public function testLoadWithSuccess(): void
    {
        self::bootKernel();
        $responses = [
            new MockResponse($this->getEpciAllResponse()),
            new MockResponse($this->getEpciCommunes()),
        ];
        $mockHttpClient = new MockHttpClient($responses);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $command = new LoadEpciCommand(
            $mockHttpClient,
            self::getContainer()->get(TerritoryRepository::class),
            self::getContainer()->get(CommuneRepository::class),
            self::getContainer()->get(EpciRepository::class),
            $entityManager,
            self::getContainer()->get(SluggerInterface::class)
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('EPCI loaded with 8 communes that belong to EPCI', $output, $output);
        $this->assertStringContainsString('35720 communes do not belong to EPCI', $output, $output);
    }

    private function getEpciAllResponse(): string
    {
        return json_encode([
            [
                'nom' => 'CC Faucigny - Glières',
                'code' => '200000172',
                'codesDepartements' => [
                    '74',
                ],
                'codesRegions' => [
                    '84',
                ],
                'population' => 27764,
            ],
        ]);
    }

    private function getEpciCommunes(): string
    {
        return json_encode([
            [
                'nom' => 'Ayse',
                'code' => '74024',
                'codeDepartement' => '74',
                'siren' => '217400241',
                'codeEpci' => '200000172',
                'codeRegion' => '84',
                'codesPostaux' => [
                    '74130',
                ],
                'population' => 2274,
            ],
            [
                'nom' => 'Bonneville',
                'code' => '74042',
                'codeDepartement' => '74',
                'siren' => '217400423',
                'codeEpci' => '200000172',
                'codeRegion' => '84',
                'codesPostaux' => [
                    '74130',
                ],
                'population' => 12895,
            ],
            [
                'nom' => 'Brizon',
                'code' => '74049',
                'codeDepartement' => '74',
                'siren' => '217400498',
                'codeEpci' => '200000172',
                'codeRegion' => '84',
                'codesPostaux' => [
                    '74130',
                ],
                'population' => 473,
            ],
        ]);
    }
}
