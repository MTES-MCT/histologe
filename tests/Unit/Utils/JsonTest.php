<?php

namespace App\Tests\Unit\Utils;

use App\Utils\Json;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testEncodeWithNullValue(): void
    {
        $this->assertNull(Json::encode(null));
    }

    public function testEncodeWIthNotNullValue(): void
    {
        $this->assertEquals('{"message":"Hi"}', Json::encode(['message' => 'Hi']));
    }
}
