<?php

namespace App\Tests\Unit\Service;

use App\Service\EmailValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailValidatorTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        /* @var ValidatorInterface validator */
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    /**
     * @dataProvider provideEmail
     */
    public function testTextWithHtml(string $email, bool $isEmailValid): void
    {
        $this->assertEquals($isEmailValid, EmailValidator::validate($this->validator, $email));
    }

    public function provideEmail(): \Generator
    {
        yield 'joey.starr@supreme.fr' => ['joey.starr@supreme.fr', true];
        yield 'lino@ärsenik.fr' => ['lino@ärsenik.fr', true];
        yield 'gaëlino.m\'bani@ärsenik.fr' => ['gaëlino.m\'bani@ärsenik.fr', true]; // à améliorer
        yield 'oxmo.puccino@time-bomb.f' => ['oxmo.puccino@time-bomb.f', true]; // à améliorer
        yield 'ronaldinho@virgule,br' => ['ronaldinho@virgule,br', false];
        yield 'Non communiqué' => ['Non communiqué', false];
        yield 'x@x.com' => ['x@x.com', true]; // à améliorer
        yield 'test@fr' => ['test@fr', false];
        yield '??' => ['??', false];
    }
}
