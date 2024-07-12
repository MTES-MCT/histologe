<?php

namespace App\Tests\Unit\Dto\Request\Signalement;

use App\Dto\Request\Signalement\CoordonneesFoyerRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CoordonneesFoyerRequestTest extends KernelTestCase
{
    private ?ValidatorInterface $validator = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = self::getContainer()->get('validator');
    }

    public function testValidateSuccess(): void
    {
        $coordonneesFoyerRequest = new CoordonneesFoyerRequest(
            typeProprio: 'PARTICULIER',
            nomStructure: null,
            civilite: 'mr',
            nom: 'Dupont',
            prenom: 'Jean',
            mail: 'jean.dupont@example.com',
            telephone: '0123456789',
            telephoneBis: '0987654321'
        );

        $this->assertSame('PARTICULIER', $coordonneesFoyerRequest->getTypeProprio());
        $this->assertNull($coordonneesFoyerRequest->getNomStructure());
        $this->assertSame('mr', $coordonneesFoyerRequest->getCivilite());
        $this->assertSame('Dupont', $coordonneesFoyerRequest->getNom());
        $this->assertSame('Jean', $coordonneesFoyerRequest->getPrenom());
        $this->assertSame('jean.dupont@example.com', $coordonneesFoyerRequest->getMail());
        $this->assertSame('0123456789', $coordonneesFoyerRequest->getTelephone());
        $this->assertSame('0987654321', $coordonneesFoyerRequest->getTelephoneBis());

        $errors = $this->validator->validate($coordonneesFoyerRequest);
        $this->assertCount(0, $errors);
    }

    public function testValidateError(): void
    {
        $coordonneesFoyerRequest = new CoordonneesFoyerRequest(
            typeProprio: 'INVALID_TYPE',
            nomStructure: str_repeat('x', 201),
            civilite: 'invalid',
            nom: str_repeat('x', 51),
            prenom: str_repeat('x', 51),
            mail: 'invalid-email',
            telephone: 'invalid-phone',
            telephoneBis: 'invalid-phone-bis'
        );

        $errors = $this->validator->validate($coordonneesFoyerRequest);
        $this->assertCount(8, $errors);
    }
}
