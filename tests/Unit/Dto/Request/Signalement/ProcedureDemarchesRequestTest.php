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
            isProprioAverti: '0',
            infoProcedureBailMoyen: 'autre',
            infoProcedureBailDate: '05/2024',
            infoProcedureBailReponse: 'Le bailleur nous a envoyé nous faire cuire un oeuf.',
            infoProcedureBailNumero: '1234567890',
            infoProcedureAssuranceContactee: 'oui',
            infoProcedureReponseAssurance: 'L\'assurance a accepté notre demande.',
            infoProcedureDepartApresTravaux: 'oui',
            preavisDepart: '2024-05-01'
        );

        $this->assertSame('0', $procedureDemarchesRequest->getIsProprioAverti());
        $this->assertSame('oui', $procedureDemarchesRequest->getInfoProcedureAssuranceContactee());
        // TODO : ajouter les nouvelles lignes
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
            isProprioAverti: '2',
            infoProcedureBailMoyen: 'pigeon voyageur',
            infoProcedureBailDate: 'hier',
            infoProcedureBailReponse: str_repeat('a', 256),// TODO : à vérifier
            infoProcedureBailNumero: str_repeat('a', 256),// TODO : à vérifier
            infoProcedureAssuranceContactee: 'oui-non',
            infoProcedureReponseAssurance: str_repeat('a', 256),
            infoProcedureDepartApresTravaux: 'oui-non',
            preavisDepart: str_repeat('a', 51)
        );

        $errors = $this->validator->validate($procedure);
        $this->assertCount(9, $errors);
    }
}
