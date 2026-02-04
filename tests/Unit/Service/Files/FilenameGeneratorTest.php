<?php

namespace App\Tests\Unit\Service\Files;

use App\Service\Files\FilenameGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

class FilenameGeneratorTest extends TestCase
{
    public function testGenerateBuildsFilenameWithPrefixSlugAndExtension(): void
    {
        $clock = new MockClock('2026-01-15 10:00:00');
        $slugger = new AsciiSlugger();

        $generator = new FilenameGenerator($slugger, $clock);

        $file = new UploadedFile(
            __FILE__,
            'Mon super fichier.jpg',
            'image/jpeg',
            null,
            true
        );

        $filename = $generator->generate($file);
        $this->assertStringStartsWith('2026/01/Mon-super-fichier', $filename);
        $this->assertStringContainsString('Mon super fichier', $generator->getTitle());
    }

    public function testPrefixForNowUsesClock(): void
    {
        $clock = new MockClock('2030-12-01');
        $slugger = new AsciiSlugger();

        $generator = new FilenameGenerator($slugger, $clock);

        $this->assertSame('2030/12', $generator->prefixForNow());
    }
}
