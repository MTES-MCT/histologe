<?php

namespace App\Tests\Unit\Utils;

use App\Utils\Json;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testEncodeWithNullValue()
    {
        $this->assertNull(Json::encode(null));
    }

    public function testEncodeWIthNotNullValue()
    {
        $this->assertEquals('{"message":"Hi"}', Json::encode(['message' => 'Hi']));
    }
}
