<?php

namespace App\Tests\Functional\Service\Import;

use App\Service\Import\CsvParser;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CsvParserTest extends KernelTestCase
{
    private const string FILEPATH = '/tmp/data.csv';

    private string $projectDir = '';

    protected function setUp(): void
    {
        self::bootKernel();
        $this->projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $this->createRandomCSV($this->projectDir.'/tmp/data.csv');
    }

    public function testDefaultOptionsCsvParser(): void
    {
        $csvParser = new CsvParser();
        $this->assertEquals(1, $csvParser->getOptions()['first_line']);
        $this->assertEquals(',', $csvParser->getOptions()['delimiter']);
        $this->assertEquals('"', $csvParser->getOptions()['enclosure']);
        $this->assertEquals('\\', $csvParser->getOptions()['escape']);
    }

    public function testParseRandomCsv(): void
    {
        $options = ['first_line' => 0, 'delimiter' => ',', 'enclosure' => '"', 'escape' => '\\'];

        $csvParser = new CsvParser($options);
        $data = $csvParser->parse($this->projectDir.self::FILEPATH);

        $this->assertIsArray($data);
        $this->assertCount(11, $data);
        $this->assertContains('Lastname', $data[0], 'The first line does not contain Lastname as column value');
        $this->assertContains('Firstname', $data[0], 'The first line does not contain Firstname as column value');
        $this->assertContains('Email', $data[0], 'The first line  does not contain Email as column value');
    }

    public function testParseAsDict(): void
    {
        $csvParser = new CsvParser();
        $dataList = $csvParser->parseAsDict($this->projectDir.self::FILEPATH);

        $this->assertCount(10, $dataList);
        foreach ($dataList as $dataItem) {
            $this->assertArrayHasKey('Lastname', $dataItem);
            $this->assertArrayHasKey('Firstname', $dataItem);
            $this->assertArrayHasKey('Email', $dataItem);
        }
    }

    public function testParseAsDictWithSemicolonDelimiter(): void
    {
        $filepath = $this->projectDir.'/tmp/data_semicolon.csv';
        $this->createRandomCSV($filepath, 5, ';');

        $csvParser = new CsvParser(['first_line' => 1, 'delimiter' => ';', 'enclosure' => '"', 'escape' => '\\']);
        $dataList = $csvParser->parseAsDict($filepath);

        $this->assertCount(5, $dataList);
        foreach ($dataList as $dataItem) {
            $this->assertArrayHasKey('Lastname', $dataItem);
            $this->assertArrayHasKey('Firstname', $dataItem);
            $this->assertArrayHasKey('Email', $dataItem);
            $this->assertCount(3, $dataItem);
        }

        unlink($filepath);
    }

    public function testParseAsDictWithMultiline(): void
    {
        $csvFile = $this->projectDir.'/tmp/multiline.csv';
        if (!is_dir(dirname($csvFile))) {
            mkdir(dirname($csvFile), 0777, true);
        }

        $content = "Header1,Header2,Header3\n";
        $content .= "Value1,\"Value 2\nwith, newline\",Value3\n";
        $content .= "Value4,\"Value 5\nwith\nmultiple\nnewlines\",Value6";

        file_put_contents($csvFile, $content);

        $csvParser = new CsvParser(['first_line' => 0, 'delimiter' => ',', 'enclosure' => '"', 'escape' => '\\']);
        $data = $csvParser->parseAsDict($csvFile);

        $this->assertCount(2, $data);
        $this->assertArrayHasKey('Header2', $data[0]);
        $this->assertEquals("Value 2\nwith, newline", $data[0]['Header2']);
        $this->assertEquals("Value 5\nwith\nmultiple\nnewlines", $data[1]['Header2']);

        unlink($csvFile);
    }

    public function testGetHeaders(): void
    {
        $options = ['first_line' => 0, 'delimiter' => ',', 'enclosure' => '"', 'escape' => '\\'];
        $csvParser = new CsvParser($options);
        $headers = $csvParser->getHeaders($this->projectDir.self::FILEPATH);

        $this->assertEquals(['Lastname', 'Firstname', 'Email'], $headers);
    }

    public function testGetContent(): void
    {
        $options = ['first_line' => 0, 'delimiter' => ',', 'enclosure' => '"', 'escape' => '\\'];
        $csvParser = new CsvParser($options);
        $content = $csvParser->getContent($this->projectDir.self::FILEPATH);

        $this->assertArrayHasKey('headers', $content);
        $this->assertArrayHasKey('rows', $content);
    }

    public function testAutoDetectDelimiter(): void
    {
        $filepathSemicolon = $this->projectDir.'/tmp/data_semicolon_detect.csv';
        $this->createRandomCSV($filepathSemicolon, 5, ';');

        $csvParser = new CsvParser();
        $csvParser->autoDetectDelimiter($filepathSemicolon);
        $this->assertEquals(';', $csvParser->getOptions()['delimiter']);

        $filepathComma = $this->projectDir.'/tmp/data_comma_detect.csv';
        $this->createRandomCSV($filepathComma, 5, ',');
        $csvParser->autoDetectDelimiter($filepathComma);
        $this->assertEquals(',', $csvParser->getOptions()['delimiter']);

        unlink($filepathSemicolon);
        unlink($filepathComma);
    }

    public function testAutoDetectFirstLine(): void
    {
        $expectedHeaders = ['nom', 'prenom', 'email'];

        $filepathLine1 = $this->projectDir.'/tmp/data_line1.csv';
        file_put_contents($filepathLine1, "nom,prenom,email\nDupont,Jean,jean@example.com");

        $csvParser1 = new CsvParser();
        $csvParser1->autoDetectFirstLine($filepathLine1, $expectedHeaders);
        $this->assertEquals(1, $csvParser1->getOptions()['first_line']);
        $this->assertEquals($expectedHeaders, $csvParser1->getHeaders($filepathLine1));

        $filepathLine2 = $this->projectDir.'/tmp/data_line2.csv';
        file_put_contents($filepathLine2, "LIGNE D'AIDE OU D'INSTRUCTION\nnom,prenom,email\nDupont,Jean,jean@example.com");

        $csvParser2 = new CsvParser();
        $csvParser2->autoDetectFirstLine($filepathLine2, $expectedHeaders);
        $this->assertEquals(2, $csvParser2->getOptions()['first_line']);
        $this->assertEquals($expectedHeaders, $csvParser2->getHeaders($filepathLine2));

        unlink($filepathLine1);
        unlink($filepathLine2);
    }

    public function testParseWithUtf8Bom(): void
    {
        $expectedHeaders = ['date arrêté', 'commune', 'nom de la voie'];
        $filepathBom = $this->projectDir.'/tmp/data_bom.csv';

        // Write UTF-8 BOM (\xEF\xBB\xBF) followed by headers and data
        file_put_contents($filepathBom, "\xEF\xBB\xBFdate arrêté;commune;nom de la voie\n01/01/2023;Marseille;Rue de la République");

        $csvParser = new CsvParser();
        $csvParser->autoDetectDelimiter($filepathBom);
        $csvParser->autoDetectFirstLine($filepathBom, $expectedHeaders);

        $headers = $csvParser->getHeaders($filepathBom);
        $this->assertEquals($expectedHeaders, $headers);
        $this->assertEquals('date arrêté', $headers[0]);

        $dict = $csvParser->parseAsDict($filepathBom);
        $this->assertCount(1, $dict);
        $this->assertArrayHasKey('date arrêté', $dict[0]);
        $this->assertEquals('01/01/2023', $dict[0]['date arrêté']);

        unlink($filepathBom);
    }

    protected function tearDown(): void
    {
        unlink($this->projectDir.self::FILEPATH);
    }

    public function createRandomCSV(string $filepath, int $line = 10, string $delimiter = ','): void
    {
        $faker = Factory::create();
        $list = [
            ['Lastname', 'Firstname', 'Email'],
        ];

        for ($i = 0; $i < $line; ++$i) {
            $list[] = [$faker->lastName(), $faker->firstName(), $faker->email()];
        }

        $fileresource = fopen($filepath, 'w');

        if (false === $fileresource) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier $filepath en écriture.");
        }
        foreach ($list as $row) {
            fputcsv($fileresource, $row, $delimiter, '"', '\\');
        }

        fclose($fileresource);
    }
}
