<?php

namespace App\Tests\Functional\Service\Import;

use App\Service\Import\CsvWriter;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CsvWriterTest extends KernelTestCase
{
    private const FILEPATH = '/tmp/csv_write.csv';
    private string $projectDir = '';

    protected function setUp(): void
    {
        self::bootKernel();
        $this->projectDir = static::getContainer()->getParameter('kernel.project_dir');
    }

    public function testWriteRowSucceed(): void
    {
        $faker = Factory::create();
        $filepath = $this->projectDir.'/tmp/csv_write.csv';

        $csvWriter = new CsvWriter(
            $filepath,
            ['firstname', 'lastname', 'city']
        );

        $this->assertEquals(1, $csvWriter->getOptions()['first_line']);
        $this->assertEquals(',', $csvWriter->getOptions()['delimiter']);
        $this->assertEquals('"', $csvWriter->getOptions()['enclosure']);
        $this->assertEquals('\\', $csvWriter->getOptions()['escape']);

        for ($i = 0; $i < 10; ++$i) {
            $csvWriter->writeRow([$faker->firstName, $faker->lastName, $faker->city]);
        }
        $csvWriter->close();

        $file = file($filepath, \FILE_SKIP_EMPTY_LINES);

        if (false === $file) {
            throw new \RuntimeException("Impossible de lire le fichier $filepath");
        }
        $this->assertCount($i + 1, $file);
        $this->assertFileExists($filepath);
        $this->assertFileIsWritable($filepath);

        $this->assertEquals(['firstname', 'lastname', 'city'], $csvWriter->getHeader());
    }

    protected function tearDown(): void
    {
        unlink($this->projectDir.self::FILEPATH);
    }
}
