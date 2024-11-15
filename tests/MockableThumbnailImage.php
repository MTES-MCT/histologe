<?php

namespace App\Tests;

use Intervention\Image\Image;
use Psr\Http\Message\StreamInterface;

class MockableThumbnailImage extends Image
{
    public function fit(int $width, ?int $height = null): Image
    {
        return static::fit($width, $height);
    }

    public function stream(?string $format = null, int $quality = 90): StreamInterface
    {
        return static::stream($format, $quality);
    }
}
