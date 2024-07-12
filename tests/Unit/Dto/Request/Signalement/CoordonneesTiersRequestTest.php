<?php

namespace App\Tests\Unit\Dto\Request\Signalement;

use App\Dto\Request\Signalement\CoordonneesTiersRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CoordonneesTiersRequestTest extends KernelTestCase
{
    private ?ValidatorInterface $validator = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = self::getContainer()->get('validator');
    }

    public function testValidateSuccess(): void
    {
        $coordonneesTiersRequest = new CoordonneesTiersRequest(
            typeProprio: 'PARTICULIER',
            nom: 'Dupont',
            prenom: 'Jean',
            mail: 'jean.dupont@example.com',
            telephone: '0123456789',
            lien: 'VOISIN',
            structure: null
        );

        $this->assertSame('PARTICULIER', $coordonneesTiersRequest->getTypeProprio());
        $this->assertSame('Dupont', $coordonneesTiersRequest->getNom());
        $this->assertSame('Jean', $coordonneesTiersRequest->getPrenom());
        $this->assertSame('jean.dupont@example.com', $coordonneesTiersRequest->getMail());
        $this->assertSame('0123456789', $coordonneesTiersRequest->getTelephone());
        $this->assertSame('VOISIN', $coordonneesTiersRequest->getLien());
        $this->assertNull($coordonneesTiersRequest->getStructure());

        $errors = $this->validator->validate($coordonneesTiersRequest);
        $this->assertCount(0, $errors);
    }

    public function testValidateError(): void
    {
        $coordonneesTiersRequest = new CoordonneesTiersRequest(
            typeProprio: 'INVALID_TYPE',
            nom: str_repeat('a', 51),
            prenom: '',
            mail: 'invalid-email',
            telephone: 'invalid-phone',
            lien: 'INVALID_LIEN',
            structure: str_repeat('b', 201)
        );

        $this->assertSame('INVALID_TYPE', $coordonneesTiersRequest->getTypeProprio());
        $this->assertSame(str_repeat('a', 51), $coordonneesTiersRequest->getNom());
        $this->assertSame('', $coordonneesTiersRequest->getPrenom());
        $this->assertSame('invalid-email', $coordonneesTiersRequest->getMail());
        $this->assertSame('invalid-phone', $coordonneesTiersRequest->getTelephone());
        $this->assertSame('INVALID_LIEN', $coordonneesTiersRequest->getLien());
        $this->assertSame(str_repeat('b', 201), $coordonneesTiersRequest->getStructure());

        $errors = $this->validator->validate($coordonneesTiersRequest);
        $this->assertCount(7, $errors);
    }
}
