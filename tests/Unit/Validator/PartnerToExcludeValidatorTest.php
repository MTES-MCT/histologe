<?php

namespace App\Tests\Unit\Validator;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Repository\PartnerRepository;
use App\Validator\PartnerToExclude;
use App\Validator\PartnerToExcludeValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<PartnerToExcludeValidator>
 */
class PartnerToExcludeValidatorTest extends ConstraintValidatorTestCase
{
    private const ERROR = 'La valeur "{{ value }}" n\'est pas valide. Elle doit être une liste d\'Id partenaires séparés par des virgules ou vide.';

    private Stub&PartnerRepository $partnerRepository;

    protected function createValidator(): ConstraintValidatorInterface
    {
        $this->partnerRepository = $this->createStub(PartnerRepository::class);

        return new PartnerToExcludeValidator($this->partnerRepository);
    }

    /**
     * @param array<mixed> $insee
     */
    #[DataProvider('provideValues')]
    public function testValues(array $insee, bool $isValid, ?string $message = null): void
    {
        $constraint = new PartnerToExclude();
        $this->validator->validate($insee, $constraint);
        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($message)
                ->setParameter('{{ value }}', implode(',', $insee))
                ->assertRaised();
        }
    }

    public static function provideValues(): \Generator
    {
        yield 'all' => [['all'], false, self::ERROR];
        yield '[2]' => [[2], true];
        yield '[22]' => [[22], true];
        yield '[22,333]' => [[22, 333], true];
        yield '[440589,44890]' => [[440589, 44890], true];
        yield 'empty string' => [[''], false, self::ERROR];
        yield 'error' => [['error'], false, self::ERROR];
        yield 'on test des trucs, et des machins' => [['on test des trucs', 'et des machins'], false, self::ERROR];
    }

    public function testPartnerOfRuleTerritoryAndTypeIsValid(): void
    {
        $territory = $this->createTerritory(1);
        $this->setObject($this->createRule($territory));
        $this->partnerRepository->method('find')->willReturn($this->createPartner($territory));

        $this->validator->validate(['12'], new PartnerToExclude());
        $this->assertNoViolation();
    }

    public function testUnknownPartner(): void
    {
        $this->setObject($this->createRule($this->createTerritory(1)));
        $this->partnerRepository->method('find')->willReturn(null);

        $constraint = new PartnerToExclude();
        $this->validator->validate(['12'], $constraint);
        $this->buildViolation($constraint->messageNotFound)
            ->setParameter('{{ id }}', '12')
            ->assertRaised();
    }

    public function testArchivedPartner(): void
    {
        $territory = $this->createTerritory(1);
        $this->setObject($this->createRule($territory));
        $this->partnerRepository->method('find')->willReturn($this->createPartner($territory)->setIsArchive(true));

        $constraint = new PartnerToExclude();
        $this->validator->validate(['12'], $constraint);
        $this->buildViolation($constraint->messageArchived)
            ->setParameter('{{ id }}', '12')
            ->assertRaised();
    }

    public function testPartnerOfAnotherTerritory(): void
    {
        $this->setObject($this->createRule($this->createTerritory(1)));
        $this->partnerRepository->method('find')->willReturn($this->createPartner($this->createTerritory(2)));

        $constraint = new PartnerToExclude();
        $this->validator->validate(['12'], $constraint);
        $this->buildViolation($constraint->messageWrongTerritory)
            ->setParameter('{{ id }}', '12')
            ->setParameter('{{ territory }}', '34 - Hérault')
            ->assertRaised();
    }

    public function testPartnerOfAnotherType(): void
    {
        $territory = $this->createTerritory(1);
        $this->setObject($this->createRule($territory));
        $this->partnerRepository->method('find')->willReturn($this->createPartner($territory)->setType(PartnerType::ARS));

        $constraint = new PartnerToExclude();
        $this->validator->validate(['12'], $constraint);
        $this->buildViolation($constraint->messageWrongType)
            ->setParameter('{{ id }}', '12')
            ->setParameter('{{ type }}', PartnerType::CAF_MSA->label())
            ->assertRaised();
    }

    private function createRule(Territory $territory): AutoAffectationRule
    {
        return (new AutoAffectationRule())->setTerritory($territory)->setPartnerType(PartnerType::CAF_MSA);
    }

    private function createPartner(Territory $territory): Partner
    {
        return (new Partner())->setTerritory($territory)->setType(PartnerType::CAF_MSA)->setIsArchive(false);
    }

    private function createTerritory(int $id): Territory
    {
        $territory = (new Territory())->setZip('34')->setName('Hérault');
        (new \ReflectionProperty(Territory::class, 'id'))->setValue($territory, $id);

        return $territory;
    }
}
