<?php

namespace App\Tests\Unit\Service\DashboardTabPanel;

use App\Service\DashboardTabPanel\TabQueryParameters;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TabQueryParametersTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidTabQueryParameters(): void
    {
        $parameters = new TabQueryParameters(
            territoireId: 1,
            communeCodePostal: '13001',
            partenairesId: [12, 14],
            sortBy: 'createdAt',
            orderBy: 'ASC'
        );

        $violations = $this->validator->validate($parameters);

        $this->assertCount(0, $violations, 'No validation violations are expected for valid data.');
    }

    public function testInvalidSortByValue(): void
    {
        $parameters = new TabQueryParameters(
            territoireId: 1,
            communeCodePostal: '13001',
            partenairesId: [12],
            sortBy: 'invalidSortBy',
            orderBy: 'ASC'
        );

        $violations = $this->validator->validate($parameters);

        $this->assertGreaterThan(0, $violations->count(), 'Cette valeur doit être l\'un des choix proposés.');
        $this->assertEquals('Cette valeur doit être l\'un des choix proposés.', $violations[0]->getMessage());
    }

    public function testInvalidOrderByValue(): void
    {
        $parameters = new TabQueryParameters(
            territoireId: 1,
            communeCodePostal: '13012',
            partenairesId: [55, 12],
            sortBy: 'createdAt',
            orderBy: 'INVALID'
        );

        $violations = $this->validator->validate($parameters);

        $this->assertGreaterThan(0, $violations->count(), 'Cette valeur doit être l\'un des choix proposés.');
        $this->assertEquals('Cette valeur doit être l\'un des choix proposés.', $violations[0]->getMessage());
    }

    public function testNullableFields(): void
    {
        $parameters = new TabQueryParameters(
            territoireId: null,
            communeCodePostal: null,
            partenairesId: null,
            sortBy: null,
            orderBy: null
        );

        $violations = $this->validator->validate($parameters);

        $this->assertCount(0, $violations, 'No validation violations are expected when nullable fields are set to null.');
    }
}
