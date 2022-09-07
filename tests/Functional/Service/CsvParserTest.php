<?php

namespace App\Tests\Functional\Service;

use App\Service\CsvParser;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CsvParserTest extends KernelTestCase
{
    private string $projectDir = '';

    private string $filepath;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $this->createRandomCSV($this->projectDir.'/tmp/data.csv');
    }

    public function testParseRandomCsv(): void
    {
        $options = ['first_line' => 0, 'delimiter' => ',', 'enclosure' => '"', 'escape' => '\\'];

        $csvParser = new CsvParser($this->projectDir.'/tmp/data.csv', $options);
        $data = $csvParser->parse();

        $this->assertIsArray($data);
        $this->assertCount(11, $data);
        $this->assertContains('Lastname', $data[0], 'The first line does not contain Lastname as column value');
        $this->assertContains('Firstname', $data[0], 'The first line does not contain Firstname as column value');
        $this->assertContains('Email', $data[0], 'The first line  does not contain Email as column value');
    }

    protected function tearDown(): void
    {
        unlink($this->projectDir.'/tmp/data.csv');
    }

    public function createRandomCSV($filepath, $line = 10): void
    {
        $faker = Factory::create();
        $list = [
            ['Lastname', 'Firstname', 'Email'],
        ];

        for ($i = 0; $i < $line; ++$i) {
            $row = [$faker->lastName(), $faker->firstName(), $faker->email()];
            $list[] = $row;
        }

        $fileresource = fopen($filepath, 'w');
        foreach ($list as $row) {
            fputcsv($fileresource, $row);
        }

        fclose($fileresource);
    }
}
