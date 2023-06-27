<?php

namespace App\Tests\Unit\Factory;

use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use App\Factory\FileFactory;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class FileFactoryTest extends TestCase
{
    use FixturesHelper;

    public function testCreateInstance(): void
    {
        $file = (new FileFactory())->createInstanceFrom(
            'sample-123.jpg',
            'sample.jpg',
            File::FILE_TYPE_PHOTO,
            $this->getSignalement(),
            $this->getUser([User::ROLE_USER_PARTNER])
        );
        $this->assertEquals('sample-123.jpg', $file->getFilename());
        $this->assertEquals('sample.jpg', $file->getTitle());
        $this->assertEquals('photo', $file->getFileType());
        $this->assertInstanceOf(Signalement::class, $file->getSignalement());
        $this->assertInstanceOf(User::class, $file->getUploadedBy());
        $this->assertInstanceOf(\DateTimeImmutable::class, $file->getCreatedAt());
    }
}
