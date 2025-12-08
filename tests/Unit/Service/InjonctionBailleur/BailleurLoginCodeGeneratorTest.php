<?php

namespace App\Tests\Unit\Service\InjonctionBailleur;

use App\Service\InjonctionBailleur\BailleurLoginCodeGenerator;
use PHPUnit\Framework\TestCase;

class BailleurLoginCodeGeneratorTest extends TestCase
{
    public function testHandleStopProcedure(): void
    {
        $newCode = BailleurLoginCodeGenerator::generate();

        $keyspace = BailleurLoginCodeGenerator::KEYSPACE;
        $this->assertEquals(19, strlen($newCode), 'The generated code should have a length of 19 characters including dashes.');
        $this->assertMatchesRegularExpression('/^['.$keyspace.']{4}-['.$keyspace.']{4}-['.$keyspace.']{4}-['.$keyspace.']{4}$/', $newCode);
    }
}
