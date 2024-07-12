<?php

namespace App\Tests\Unit\Dto\Request\Signalement;

use App\Dto\Request\Signalement\InformationsLogementRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InformationsLogementRequestTest extends KernelTestCase
{
    private ?ValidatorInterface $validator = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = self::getContainer()->get('validator');
    }

    public function testValidateSuccess(): void
    {
        $informationsLogementRequest = new InformationsLogementRequest(
            nombrePersonnes: '3',
            compositionLogementEnfants: 'oui',
            dateEntree: '2022-01-01',
            bailleurDateEffetBail: '2022-01-01',
            bailDpeBail: 'oui',
            bailDpeEtatDesLieux: 'oui',
            bailDpeDpe: 'oui',
            loyer: '750',
            loyersPayes: 'oui',
            anneeConstruction: '2000'
        );

        $this->assertSame('3', $informationsLogementRequest->getNombrePersonnes());
        $this->assertSame('oui', $informationsLogementRequest->getCompositionLogementEnfants());
        $this->assertSame('2022-01-01', $informationsLogementRequest->getDateEntree());
        $this->assertSame('2022-01-01', $informationsLogementRequest->getBailleurDateEffetBail());
        $this->assertSame('oui', $informationsLogementRequest->getBailDpeBail());
        $this->assertSame('oui', $informationsLogementRequest->getBailDpeEtatDesLieux());
        $this->assertSame('oui', $informationsLogementRequest->getBailDpeDpe());
        $this->assertSame('750', $informationsLogementRequest->getLoyer());
        $this->assertSame('oui', $informationsLogementRequest->getLoyersPayes());
        $this->assertSame('2000', $informationsLogementRequest->getAnneeConstruction());

        $errors = $this->validator->validate($informationsLogementRequest);
        $this->assertCount(0, $errors);
    }

    public function testValidateError(): void
    {
        $informationsLogementRequest = new InformationsLogementRequest(
            nombrePersonnes: '-1',
            compositionLogementEnfants: 'maybe',
            dateEntree: 'invalid-date',
            bailleurDateEffetBail: 'invalid-date',
            bailDpeBail: 'unknown',
            bailDpeEtatDesLieux: 'unknown',
            bailDpeDpe: 'unknown',
            loyer: 'invalid-loyer',
            loyersPayes: 'unknown',
            anneeConstruction: '20'
        );

        $errors = $this->validator->validate($informationsLogementRequest);
        $this->assertCount(10, $errors);
    }
}
