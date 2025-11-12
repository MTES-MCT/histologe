<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Service\Interconnection\Esabora\AttachmentsUtils;
use PHPUnit\Framework\TestCase;

class AttachmentsUtilsTest extends TestCase
{
    public function testComputeTotalSizeWithValidData(): void
    {
        $input = [
            ['documentSize' => 200],
            ['documentSize' => 300],
            ['documentSize' => 500],
        ];

        $result = AttachmentsUtils::computeTotalSize($input);

        $this->assertSame(1000, $result);
    }

    public function testComputeTotalSizeWithEmptyArray(): void
    {
        $input = [];

        $result = AttachmentsUtils::computeTotalSize($input);

        $this->assertSame(0, $result);
    }
}
