<?php

namespace App\Tests\Unit\Validator;

use App\Validator\EmailFormatValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EmailFormatValidatorTest extends KernelTestCase
{
    /**
     * @dataProvider provideEmail
     */
    public function testEmailsFormat(string $email, bool $isEmailValid): void
    {
        $this->assertEquals($isEmailValid, EmailFormatValidator::validate($email));
    }

    public function provideEmail(): \Generator
    {
        yield 'joey.starr@supreme.fr' => ['joey.starr@supreme.fr', true];
        yield 'lino@ärsenik.fr' => ['lino@ärsenik.fr', true];
        yield 'gaëlino.m\'bani@ärsenik.fr' => ['gaëlino.m\'bani@ärsenik.fr', true]; // à améliorer
        yield 'oxmo.puccino@time-bomb.f' => ['oxmo.puccino@time-bomb.f', false];
        yield 'oxmo.puccino@time-bomb.fr' => ['oxmo.puccino@time-bomb.fr', true];
        yield 'ronaldinho@virgule,br' => ['ronaldinho@virgule,br', false];
        yield 'Non communiqué' => ['Non communiqué', false];
        yield 'x@x.com' => ['x@x.com', true]; // à améliorer
        yield 'test@fr' => ['test@fr', false];
        yield '??' => ['??', false];
    }
}
