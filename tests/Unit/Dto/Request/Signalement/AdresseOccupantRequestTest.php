<?php

namespace App\Tests\Unit\Dto\Request\Signalement;

use App\Dto\Request\Signalement\AdresseOccupantRequest;
use App\Entity\Enum\EtageType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AdresseOccupantRequestTest extends KernelTestCase
{
    private ?ValidatorInterface $validator = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = static::getContainer()->get('validator');
    }

    public function testValidateSuccess(): void
    {
        $adresseOccupantRequest = new AdresseOccupantRequest(
            adresse: '123 Rue de Exemple',
            codePostal: '75001',
            ville: 'Paris',
            etage: EtageType::RDC->value,
            escalier: 'A',
            numAppart: '42',
            autre: 'Proche de la boulangerie',
            insee: '75056',
            manual: '1',
            needResetInsee: '1'
        );

        $this->assertSame('123 Rue de Exemple', $adresseOccupantRequest->getAdresse());
        $this->assertSame('75001', $adresseOccupantRequest->getCodePostal());
        $this->assertSame('Paris', $adresseOccupantRequest->getVille());
        $this->assertSame(EtageType::RDC->value, $adresseOccupantRequest->getEtage());
        $this->assertNull($adresseOccupantRequest->getEtagePrecision());
        $this->assertSame('A', $adresseOccupantRequest->getEscalier());
        $this->assertSame('42', $adresseOccupantRequest->getNumAppart());
        $this->assertSame('Proche de la boulangerie', $adresseOccupantRequest->getAutre());
        $this->assertSame('75056', $adresseOccupantRequest->getInsee());
        $this->assertSame('1', $adresseOccupantRequest->getManual());
        $this->assertSame('1', $adresseOccupantRequest->getNeedResetInsee());

        $errors = $this->validator->validate($adresseOccupantRequest);
        $this->assertCount(0, $errors);
    }

    public function testValidateSuccessEtageAutreAvecPrecision(): void
    {
        $adresseOccupantRequest = new AdresseOccupantRequest(
            adresse: '123 Rue de Exemple',
            codePostal: '75001',
            ville: 'Paris',
            etage: EtageType::AUTRE->value,
            etagePrecision: 'Combles',
        );

        $this->assertSame(EtageType::AUTRE->value, $adresseOccupantRequest->getEtage());
        $this->assertSame('Combles', $adresseOccupantRequest->getEtagePrecision());

        $errors = $this->validator->validate($adresseOccupantRequest);
        $this->assertCount(0, $errors);
    }

    public function testValidateErrorEtageAutreSansPrecision(): void
    {
        $adresseOccupantRequest = new AdresseOccupantRequest(
            adresse: '123 Rue de Exemple',
            codePostal: '75001',
            ville: 'Paris',
            etage: EtageType::AUTRE->value,
        );

        $errors = $this->validator->validate($adresseOccupantRequest);
        $this->assertCount(1, $errors);
    }

    public function testValidateError(): void
    {
        $adresseOccupantRequestInvalide = new AdresseOccupantRequest(
            adresse: '',
            codePostal: '123',
            ville: '',
            etage: 'invalid_choice',
            etagePrecision: str_repeat('E', 21),
            escalier: 'EscalierInvalide',
            numAppart: 'NumAppInvalide',
            autre: str_repeat('x', 256),
            insee: '123',
            manual: '2',
            needResetInsee: '2'
        );

        $errors = $this->validator->validate($adresseOccupantRequestInvalide);
        $this->assertCount(12, $errors);
    }
}
