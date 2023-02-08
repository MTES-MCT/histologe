<?php

namespace App\Tests\Functional\Service;

use App\Service\Files\HeicToJpegConverter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class HeicToJpegConverterTest extends KernelTestCase
{
    public string $projectDir = '';
    public string $fixturesPath = '/src/DataFixtures/Images/';
    public string $originalFilename = 'sample';
    public string $extension = '.heic';

    protected function setUp(): void
    {
        self::bootKernel();
        $this->projectDir = static::getContainer()->getParameter('kernel.project_dir');
    }

    public function testConvertHeicFile(): void
    {
        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);

        $heicToJpegConverter = new HeicToJpegConverter($parameterBag);
        $convertedFilePath = $heicToJpegConverter->convert($this->projectDir.$this->fixturesPath.$this->originalFilename.$this->extension, $this->originalFilename.$this->extension);

        $this->assertInstanceOf(HeicToJpegConverter::class, $heicToJpegConverter);
        $this->assertIsString($convertedFilePath);
        $this->assertFileExists($convertedFilePath);
    }
}
