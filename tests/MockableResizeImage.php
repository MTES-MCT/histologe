<?php

namespace App\Tests;

use Intervention\Image\Image;
use Psr\Http\Message\StreamInterface;

class MockableResizeImage extends Image
{
    public function resize(int $width, int $height, ?callable $callback = null): Image
    {
        return static::resize($width, $height, $callback);
    }

    public function stream(?string $format = null, int $quality = 90): StreamInterface
    {
        return static::stream($format, $quality);
    }
}
