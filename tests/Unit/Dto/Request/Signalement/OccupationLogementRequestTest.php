<?php

namespace App\Tests\Unit\Dto\Request\Signalement;

use App\Dto\Request\Signalement\OccupationLogementRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OccupationLogementRequestTest extends KernelTestCase
{
    private ?ValidatorInterface $validator = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = static::getContainer()->get('validator');
    }

    public function testValidateSuccess(): void
    {
        $occupationLogementRequest = new OccupationLogementRequest(
            nombrePersonnes: '3',
            compositionLogementEnfants: 'oui',
            compositionLogementNombreEnfants: '2',
            dateEntree: '2022-01-01',
            bailleurDateEffetBail: '2022-01-01',
            bailDpeBail: 'oui',
            bailDpeInvariant: 'abcd12ef34',
            bailDpeEtatDesLieux: 'oui',
            loyer: '750',
            loyersPayes: 'oui',
            anneeConstruction: '2000',
            autresOccupantsDesordre: 'oui',
        );

        $this->assertSame('3', $occupationLogementRequest->getNombrePersonnes());
        $this->assertSame('oui', $occupationLogementRequest->getCompositionLogementEnfants());
        $this->assertSame('2022-01-01', $occupationLogementRequest->getDateEntree());
        $this->assertSame('2022-01-01', $occupationLogementRequest->getBailleurDateEffetBail());
        $this->assertSame('oui', $occupationLogementRequest->getBailDpeBail());
        $this->assertSame('abcd12ef34', $occupationLogementRequest->getBailDpeInvariant());
        $this->assertSame('oui', $occupationLogementRequest->getBailDpeEtatDesLieux());
        $this->assertSame('750', $occupationLogementRequest->getLoyer());
        $this->assertSame('oui', $occupationLogementRequest->getLoyersPayes());
        $this->assertSame('2000', $occupationLogementRequest->getAnneeConstruction());
        $this->assertSame('oui', $occupationLogementRequest->getAutresOccupantsDesordre());

        $errors = $this->validator->validate($occupationLogementRequest);
        $this->assertCount(0, $errors);
    }

    public function testValidateError(): void
    {
        $occupationLogementRequest = new OccupationLogementRequest(
            nombrePersonnes: '-1',
            compositionLogementEnfants: 'maybe',
            compositionLogementNombreEnfants: '-1',
            dateEntree: 'invalid-date',
            bailleurDateEffetBail: 'invalid-date',
            bailDpeBail: 'unknown',
            bailDpeInvariant: 'invalid_invariant_fiscal',
            bailDpeEtatDesLieux: 'unknown',
            loyer: 'invalid-loyer',
            loyersPayes: 'unknown',
            anneeConstruction: '20',
            autresOccupantsDesordre: 'PLOP',
        );

        $errors = $this->validator->validate($occupationLogementRequest);
        $this->assertCount(12, $errors);
    }
}
