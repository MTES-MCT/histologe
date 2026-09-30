<?php

namespace App\Tests\Unit\Validator;

use App\Entity\AutoAffectationRule;
use App\Entity\Territory;
use App\Entity\Zone;
use App\Repository\ZoneRepository;
use App\Validator\ZoneIds;
use App\Validator\ZoneIdsValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<ZoneIdsValidator>
 */
class ZoneIdsValidatorTest extends ConstraintValidatorTestCase
{
    private Stub&ZoneRepository $zoneRepository;

    protected function createValidator(): ConstraintValidatorInterface
    {
        $this->zoneRepository = $this->createStub(ZoneRepository::class);

        return new ZoneIdsValidator($this->zoneRepository);
    }

    /**
     * @param array<mixed> $zoneIds
     */
    #[DataProvider('provideFormatValues')]
    public function testFormat(array $zoneIds, bool $isValid): void
    {
        $constraint = new ZoneIds();
        $this->validator->validate($zoneIds, $constraint);
        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->setParameter('{{ value }}', implode(',', $zoneIds))
                ->assertRaised();
        }
    }

    public static function provideFormatValues(): \Generator
    {
        yield '[2]' => [['2'], true];
        yield '[22,333]' => [['22', '333'], true];
        yield 'empty' => [[], true];
        yield 'empty string' => [[''], false];
        yield 'all' => [['all'], false];
        yield 'mixed' => [['12', 'zone'], false];
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new ZoneIds());
        $this->assertNoViolation();
    }

    public function testZoneOfRuleTerritoryIsValid(): void
    {
        $territory = $this->createTerritory(1);
        $this->setObject((new AutoAffectationRule())->setTerritory($territory));
        $this->zoneRepository->method('find')->willReturn((new Zone())->setTerritory($territory));

        $this->validator->validate(['12'], new ZoneIds());
        $this->assertNoViolation();
    }

    public function testUnknownZone(): void
    {
        $this->setObject((new AutoAffectationRule())->setTerritory($this->createTerritory(1)));
        $this->zoneRepository->method('find')->willReturn(null);

        $constraint = new ZoneIds();
        $this->validator->validate(['12'], $constraint);
        $this->buildViolation($constraint->messageNotFound)
            ->setParameter('{{ id }}', '12')
            ->assertRaised();
    }

    public function testZoneOfAnotherTerritory(): void
    {
        $this->setObject((new AutoAffectationRule())->setTerritory($this->createTerritory(1)));
        $this->zoneRepository->method('find')->willReturn((new Zone())->setTerritory($this->createTerritory(2)));

        $constraint = new ZoneIds();
        $this->validator->validate(['12'], $constraint);
        $this->buildViolation($constraint->messageWrongTerritory)
            ->setParameter('{{ id }}', '12')
            ->setParameter('{{ territory }}', '34 - Hérault')
            ->assertRaised();
    }

    private function createTerritory(int $id): Territory
    {
        $territory = (new Territory())->setZip('34')->setName('Hérault');
        (new \ReflectionProperty(Territory::class, 'id'))->setValue($territory, $id);

        return $territory;
    }
}
