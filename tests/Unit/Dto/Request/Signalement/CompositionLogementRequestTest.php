<?php

namespace App\Tests\Unit\Dto\Request\Signalement;

use App\Dto\Request\Signalement\CompositionLogementRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validation;

class CompositionLogementRequestTest extends KernelTestCase
{
    public function testValidateSuccess(): void
    {
        $compositionLogementRequest = new CompositionLogementRequest(
            type: 'maison',
            typeLogementNatureAutrePrecision: null,
            typeCompositionLogement: 'piece_unique',
            superficie: '80',
            compositionLogementHauteur: 'oui',
            compositionLogementNbPieces: '3',
            nombreEtages: '2',
            typeLogementRdc: 'oui',
            typeLogementDernierEtage: 'non',
            typeLogementSousCombleSansFenetre: 'non',
            typeLogementSousSolSansFenetre: 'non',
            typeLogementCommoditesPieceAVivre9m: 'oui',
            typeLogementCommoditesCuisine: 'oui',
            typeLogementCommoditesCuisineCollective: 'non',
            typeLogementCommoditesSalleDeBain: 'oui',
            typeLogementCommoditesSalleDeBainCollective: 'non',
            typeLogementCommoditesWc: 'oui',
            typeLogementCommoditesWcCollective: 'non',
            typeLogementCommoditesWcCuisine: 'non'
        );

        $this->assertSame('maison', $compositionLogementRequest->getType());
        $this->assertNull($compositionLogementRequest->getTypeLogementNatureAutrePrecision());
        $this->assertSame('piece_unique', $compositionLogementRequest->getTypeCompositionLogement());
        $this->assertSame('80', $compositionLogementRequest->getSuperficie());
        $this->assertSame('oui', $compositionLogementRequest->getCompositionLogementHauteur());
        $this->assertSame('3', $compositionLogementRequest->getCompositionLogementNbPieces());
        $this->assertSame('2', $compositionLogementRequest->getNombreEtages());
        $this->assertSame('oui', $compositionLogementRequest->getTypeLogementRdc());
        $this->assertSame('non', $compositionLogementRequest->getTypeLogementDernierEtage());
        $this->assertSame('non', $compositionLogementRequest->getTypeLogementSousCombleSansFenetre());
        $this->assertSame('non', $compositionLogementRequest->getTypeLogementSousSolSansFenetre());
        $this->assertSame('oui', $compositionLogementRequest->getTypeLogementCommoditesPieceAVivre9m());
        $this->assertSame('oui', $compositionLogementRequest->getTypeLogementCommoditesCuisine());
        $this->assertSame('non', $compositionLogementRequest->getTypeLogementCommoditesCuisineCollective());
        $this->assertSame('oui', $compositionLogementRequest->getTypeLogementCommoditesSalleDeBain());
        $this->assertSame('non', $compositionLogementRequest->getTypeLogementCommoditesSalleDeBainCollective());
        $this->assertSame('oui', $compositionLogementRequest->getTypeLogementCommoditesWc());
        $this->assertSame('non', $compositionLogementRequest->getTypeLogementCommoditesWcCollective());
        $this->assertSame('non', $compositionLogementRequest->getTypeLogementCommoditesWcCuisine());

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($compositionLogementRequest);
        $this->assertCount(0, $errors);
    }

    public function testValidateError(): void
    {
        $compositionLogementRequest = new CompositionLogementRequest(
            type: 'invalid_type',
            typeLogementNatureAutrePrecision: str_repeat('a', 101),
            typeCompositionLogement: 'invalid_choice',
            superficie: 'invalid_superficie',
            compositionLogementHauteur: 'invalid_choice',
            compositionLogementNbPieces: 'invalid_nb',
            nombreEtages: 'invalid_nb',
            typeLogementRdc: 'oui',
            typeLogementDernierEtage: 'oui',
            typeLogementSousCombleSansFenetre: 'invalid_choice',
            typeLogementSousSolSansFenetre: 'invalid_choice',
            typeLogementCommoditesPieceAVivre9m: 'invalid_choice',
            typeLogementCommoditesCuisine: 'invalid_choice',
            typeLogementCommoditesCuisineCollective: 'invalid_choice',
            typeLogementCommoditesSalleDeBain: 'invalid_choice',
            typeLogementCommoditesSalleDeBainCollective: 'invalid_choice',
            typeLogementCommoditesWc: 'invalid_choice',
            typeLogementCommoditesWcCollective: 'invalid_choice',
            typeLogementCommoditesWcCuisine: 'invalid_choice'
        );

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $errors = $validator->validate($compositionLogementRequest);
        $this->assertCount(20, $errors);
    }
}
