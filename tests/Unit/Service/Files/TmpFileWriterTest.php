<?php

namespace App\Tests\Unit\Service\Files;

use App\Service\Files\TmpFileWriter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TmpFileWriterTest extends KernelTestCase
{
    private string $projectDir = '';

    protected function setUp(): void
    {
        self::bootKernel();
        $this->projectDir = static::getContainer()->getParameter('kernel.project_dir');
    }

    /**
     * @throws \Exception
     */
    public function testPutContentsWritesFile(): void
    {
        $writer = new TmpFileWriter();
        $filePath = $this->projectDir.'/tmp/2026/01/test.txt';
        $content = 'Hello World';
        $writer->putContents($filePath, $content);
        $this->assertFileExists($filePath);
        $this->assertSame($content, file_get_contents($filePath));

        @unlink($filePath);
    }
}
