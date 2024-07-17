<?php

namespace App\Tests\Unit\Dto\Request\Signalement;

use App\Dto\Request\Signalement\SituationFoyerRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SituationFoyerRequestTest extends KernelTestCase
{
    private ?ValidatorInterface $validator = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = self::getContainer()->get('validator');
    }

    public function testValidateSuccess(): void
    {
        $situationFoyerRequest = new SituationFoyerRequest(
            isLogementSocial: 'oui',
            isRelogement: 'non',
            isAllocataire: 'CAF',
            dateNaissanceOccupant: '1990-01-01',
            numAllocataire: '123456789',
            logementSocialMontantAllocation: '500',
            travailleurSocialQuitteLogement: 'oui',
            travailleurSocialPreavisDepart: 'oui',
            travailleurSocialAccompagnement: 'oui',
            travailleurSocialAccompagnementDeclarant: '1',
            beneficiaireRsa: 'oui',
            beneficiaireFsl: 'non',
            revenuFiscal: '20000'
        );

        $this->assertSame('oui', $situationFoyerRequest->getIsLogementSocial());
        $this->assertSame('non', $situationFoyerRequest->getIsRelogement());
        $this->assertSame('CAF', $situationFoyerRequest->getIsAllocataire());
        $this->assertSame('1990-01-01', $situationFoyerRequest->getDateNaissanceOccupant());
        $this->assertSame('123456789', $situationFoyerRequest->getNumAllocataire());
        $this->assertSame('500', $situationFoyerRequest->getLogementSocialMontantAllocation());
        $this->assertSame('oui', $situationFoyerRequest->getTravailleurSocialQuitteLogement());
        $this->assertSame('oui', $situationFoyerRequest->getTravailleurSocialPreavisDepart());
        $this->assertSame('oui', $situationFoyerRequest->getTravailleurSocialAccompagnement());
        $this->assertSame('1', $situationFoyerRequest->getTravailleurSocialAccompagnementDeclarant());
        $this->assertSame('oui', $situationFoyerRequest->getBeneficiaireRsa());
        $this->assertSame('non', $situationFoyerRequest->getBeneficiaireFsl());
        $this->assertSame('20000', $situationFoyerRequest->getRevenuFiscal());

        $errors = $this->validator->validate($situationFoyerRequest);
        $this->assertCount(0, $errors);
    }

    public function testValidateError(): void
    {
        $situationFoyerRequest = new SituationFoyerRequest(
            isLogementSocial: 'maybe',
            isRelogement: '',
            isAllocataire: 'unknown',
            dateNaissanceOccupant: 'invalid-date',
            numAllocataire: str_repeat('a', 51),
            logementSocialMontantAllocation: 'invalid-amount',
            travailleurSocialQuitteLogement: 'maybe',
            travailleurSocialPreavisDepart: '',
            travailleurSocialAccompagnement: 'unknown',
            travailleurSocialAccompagnementDeclarant: str_repeat('b', 51),
            beneficiaireRsa: 'unknown',
            beneficiaireFsl: 'unknown',
            revenuFiscal: str_repeat('c', 51)
        );

        $errors = $this->validator->validate($situationFoyerRequest);
        $this->assertCount(12, $errors);
    }
}
