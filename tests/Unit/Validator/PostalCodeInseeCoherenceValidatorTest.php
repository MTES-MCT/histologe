<?php

namespace App\Tests\Unit\Validator;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Commune;
use App\Repository\CommuneRepository;
use App\Service\Signalement\PostalCodeHomeChecker;
use App\Validator\PostalCodeInseeCoherence;
use App\Validator\PostalCodeInseeCoherenceValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<PostalCodeInseeCoherenceValidator>
 */
class PostalCodeInseeCoherenceValidatorTest extends ConstraintValidatorTestCase
{
    private CommuneRepository&MockObject $communeRepository;
    private PostalCodeHomeChecker&MockObject $postalCodeHomeChecker;

    protected function createValidator(): ConstraintValidatorInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->communeRepository = $this->createMock(CommuneRepository::class);
        $this->postalCodeHomeChecker = $this->createMock(PostalCodeHomeChecker::class);

        $entityManager
            ->method('getRepository')
            ->with(Commune::class)
            ->willReturn($this->communeRepository);

        return new PostalCodeInseeCoherenceValidator($entityManager, $this->postalCodeHomeChecker);
    }

    /**
     * @dataProvider provideCoherenceCases
     */
    public function testPostalCodeInseeCoherence(
        string $postalCode,
        string $rawInseeCode,
        string $normalizedInseeCode,
        bool $communeExists,
        ?string $expectedViolationMessage,
    ): void {
        $request = $this->createMock(SignalementDraftRequest::class);
        $request->method('getAdresseLogementAdresseDetailCodePostal')->willReturn($postalCode);
        $request->method('getAdresseLogementAdresseDetailInsee')->willReturn($rawInseeCode);

        $this->postalCodeHomeChecker
            ->expects($this->once())
            ->method('normalizeInseeCode')
            ->with($postalCode, $rawInseeCode)
            ->willReturn($normalizedInseeCode);

        $this->communeRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'codePostal' => $postalCode,
                'codeInsee' => $normalizedInseeCode,
            ])
            ->willReturn($communeExists ? $this->createMock(Commune::class) : null);

        $this->validator->validate($request, new PostalCodeInseeCoherence());

        if (null !== $expectedViolationMessage) {
            $this->buildViolation($expectedViolationMessage)
                ->atPath('property.path.adresseLogementAdresseDetailCodePostal')
                ->assertRaised();

            return;
        }

        $this->assertNoViolation();
    }

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

        $request = $this->createMock(SignalementDraftRequest::class);
        $wrongConstraint = $this->createMock(Constraint::class);

        $this->validator->validate($request, $wrongConstraint);
    }
}
