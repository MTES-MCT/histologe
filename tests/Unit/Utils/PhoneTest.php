<?php

namespace App\Tests\Unit\Utils;

use App\Utils\Phone;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    /**
     * @dataProvider providePhone
     */
    public function testFormatPhone(?string $phoneNumber, ?string $phoneFormatted, ?string $phoneNationalFormatted): void
    {
        $this->assertEquals($phoneFormatted, Phone::format($phoneNumber));
        $this->assertEquals($phoneNationalFormatted, Phone::format($phoneNumber, true));
    }

    public function providePhone(): \Generator
    {
        yield 'null' => [null, null, null];

        yield 'empty string' => ['', '', ''];

        yield 'invalid phone number' => ['invalid phone number', 'invalid phone number', 'invalid phone number'];

        yield 'phone number without country code' => ['0620212223', '+33620212223', '0620212223'];

        yield 'phone number with country code' => ['+33620212223', '+33620212223', '0620212223'];

        yield 'phone number with country code and spaces' => ['+33 6 20 21 22 23', '+33620212223', '0620212223'];

        yield 'phone number from foreign country' => ['+49123456789', '+49123456789', '123456789'];
    }
}
