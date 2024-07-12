<?php

namespace App\Tests\Unit\Dto\Request\Signalement;

use App\Dto\Request\Signalement\ProcedureDemarchesRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProcedureDemarchesRequestTest extends KernelTestCase
{
    private ?ValidatorInterface $validator = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = self::getContainer()->get('validator');
    }

    public function testValidateSuccess(): void
    {
        $procedureDemarchesRequest = new ProcedureDemarchesRequest(
            '0',
            'oui',
            'L\'assurance a accepté notre demande.',
            'oui',
            '2024-05-01'
        );

        $this->assertSame('0', $procedureDemarchesRequest->getIsProprioAverti());
        $this->assertSame('oui', $procedureDemarchesRequest->getInfoProcedureAssuranceContactee());
        $this->assertSame('L\'assurance a accepté notre demande.',
            $procedureDemarchesRequest->getInfoProcedureReponseAssurance());
        $this->assertSame('oui', $procedureDemarchesRequest->getInfoProcedureDepartApresTravaux());
        $this->assertSame('2024-05-01', $procedureDemarchesRequest->getPreavisDepart());

        $errors = $this->validator->validate($procedureDemarchesRequest);
        $this->assertCount(0, $errors);
    }

    public function testValidateError(): void
    {
        $procedure = new ProcedureDemarchesRequest(
            '2',
            'oui-non',
            str_repeat('a', 256),
            'oui-non',
            str_repeat('a', 51)
        );

        $errors = $this->validator->validate($procedure);
        $this->assertCount(5, $errors);
    }
}
