<?php

namespace App\Tests\Functional\Service\Import;

use App\Service\Import\CsvParser;
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

        $csvParser = new CsvParser($options);
        $data = $csvParser->parse($this->projectDir.'/tmp/data.csv');

        $this->assertIsArray($data);
        $this->assertCount(11, $data);
        $this->assertContains('Lastname', $data[0], 'The first line does not contain Lastname as column value');
        $this->assertContains('Firstname', $data[0], 'The first line does not contain Firstname as column value');
        $this->assertContains('Email', $data[0], 'The first line  does not contain Email as column value');
    }

    public function testParseAsDict(): void
    {
        $options = ['first_line' => 0, 'delimiter' => ',', 'enclosure' => '"', 'escape' => '\\'];
        $csvParser = new CsvParser($options);
        $dataList = $csvParser->parseAsDict($this->projectDir.'/tmp/data.csv');

        foreach ($dataList as $dataItem) {
            $this->assertArrayHasKey('Lastname', $dataItem);
            $this->assertArrayHasKey('Firstname', $dataItem);
            $this->assertArrayHasKey('Email', $dataItem);
        }
    }

    public function testGetHeaders(): void
    {
        $options = ['first_line' => 0, 'delimiter' => ',', 'enclosure' => '"', 'escape' => '\\'];
        $csvParser = new CsvParser($options);
        $headers = $csvParser->getHeaders($this->projectDir.'/tmp/data.csv');

        $this->assertEquals(['Lastname', 'Firstname', 'Email'], $headers);
    }

    public function testGetContent(): void
    {
        $options = ['first_line' => 0, 'delimiter' => ',', 'enclosure' => '"', 'escape' => '\\'];
        $csvParser = new CsvParser($options);
        $content = $csvParser->getContent($this->projectDir.'/tmp/data.csv');

        $this->assertArrayHasKey('headers', $content);
        $this->assertArrayHasKey('rows', $content);
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
