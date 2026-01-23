<?php

namespace App\Tests\Unit\Entity;

use App\Entity\File;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testFileWithDisplayFilename(): void
    {
        $file = new File();
        $file->setFilename('2026/01/mon-super-fichier.pdf');

        $this->assertEquals('mon-super-fichier.pdf', $file->getDisplayFilename());
    }
}
