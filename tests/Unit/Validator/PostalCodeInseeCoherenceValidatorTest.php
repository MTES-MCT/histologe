<?php

namespace App\Tests\Unit\Validator;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Commune;
use App\Repository\CommuneRepository;
use App\Service\Signalement\PostalCodeHomeChecker;
use App\Validator\PostalCodeInseeCoherence;
use App\Validator\PostalCodeInseeCoherenceValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<PostalCodeInseeCoherenceValidator>
 */
class PostalCodeInseeCoherenceValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new PostalCodeInseeCoherenceValidator(
            $this->createStub(PostalCodeHomeChecker::class),
            $this->createStub(CommuneRepository::class),
        );
    }

    #[DataProvider('provideCoherenceCases')]
    public function testPostalCodeInseeCoherence(
        string $postalCode,
        string $rawInseeCode,
        string $normalizedInseeCode,
        bool $communeExists,
        ?string $expectedViolationMessage,
    ): void {
        $request = $this->createStub(SignalementDraftRequest::class);
        $request->method('getAdresseLogementAdresseDetailCodePostal')->willReturn($postalCode);
        $request->method('getAdresseLogementAdresseDetailInsee')->willReturn($rawInseeCode);

        $postalCodeHomeChecker = $this->createMock(PostalCodeHomeChecker::class);
        $postalCodeHomeChecker
            ->expects($this->once())
            ->method('normalizeInseeCode')
            ->with($postalCode, $rawInseeCode)
            ->willReturn($normalizedInseeCode);

        $communeRepository = $this->createMock(CommuneRepository::class);
        $communeRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'codePostal' => $postalCode,
                'codeInsee' => $normalizedInseeCode,
            ])
            ->willReturn($communeExists ? $this->createStub(Commune::class) : null);

        $this->validator = new PostalCodeInseeCoherenceValidator($postalCodeHomeChecker, $communeRepository);
        $this->validator->initialize($this->context);
        $this->validator->validate($request, new PostalCodeInseeCoherence());

        if (null !== $expectedViolationMessage) {
            $this->buildViolation($expectedViolationMessage)
                ->atPath('property.path.adresseLogementAdresseDetailCodePostal')
                ->assertRaised();

            return;
        }

        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{postalCode: string, rawInseeCode: string, normalizedInseeCode: string, communeExists: bool, expectedViolationMessage: string|null}>
     */
    public static function provideCoherenceCases(): iterable
    {
        yield 'ajoute une violation si aucune commune correspond' => [
            'postalCode' => '13001',
            'rawInseeCode' => '13055',
            'normalizedInseeCode' => '13201',
            'communeExists' => false,
            'expectedViolationMessage' => 'Le code postal 13001 et le code INSEE 13201 ne sont pas cohérents.',
        ];

        yield 'aucune violation si la commune existe' => [
            'postalCode' => '44000',
            'rawInseeCode' => '44109',
            'normalizedInseeCode' => '44109',
            'communeExists' => true,
            'expectedViolationMessage' => null,
        ];
    }

    public function testThrowsWhenValueIsNotSignalementDraftRequest(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate('not_a_request', new PostalCodeInseeCoherence());
    }

    public function testThrowsWhenConstraintIsNotPostalCodeInseeCoherence(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $request = $this->createStub(SignalementDraftRequest::class);
        $wrongConstraint = $this->createStub(Constraint::class);

        $this->validator->validate($request, $wrongConstraint);
    }
}
