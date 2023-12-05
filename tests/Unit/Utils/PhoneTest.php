<?php

namespace App\Tests\Unit\Utils;

use App\Utils\Phone;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    public function testFormatWithNullPhone(): void
    {
        $this->assertNull(Phone::format(null));
    }

    public function testFormatWithEmptyPhone(): void
    {
        $this->assertEquals('', Phone::format(''));
    }

    public function testFormatWithInvalidPhone(): void
    {
        $this->assertEquals('invalid', Phone::format('invalid'));
    }

    public function testFormatWithValidPhone(): void
    {
        $this->assertEquals('+33123456789', Phone::format('123456789'));
    }

    public function testFormatWithValidPhoneAndNationalFormat(): void
    {
        $this->assertEquals('0123456789', Phone::format('123456789', true));
    }

    public function testFormatWithJsonPhone(): void
    {
        $phoneJson = json_encode(['phone_number' => '123456789', 'country_code' => 'FR']);
        $this->assertEquals('+33123456789', Phone::format($phoneJson));
    }

    public function testFormatWithJsonPhoneAndNationalFormat(): void
    {
        $phoneJson = json_encode(['phone_number' => '123456789', 'country_code' => 'FR']);
        $this->assertEquals('0123456789', Phone::format($phoneJson, true));
    }
}
