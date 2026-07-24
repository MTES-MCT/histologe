<?php

namespace App\Tests\Unit\Dto\Request\Signalement;

use App\Dto\Request\Signalement\AddressesHistorySearchQuery;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AddressesHistorySearchQueryTest extends KernelTestCase
{
    public function testGetFiltersWithAllParameters(): void
    {
        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get('validator');

        $query = new AddressesHistorySearchQuery(
            territoire: '13',
            adresse: 'rue de la paix',
            communes: ['Marseille', 'Aix-en-Provence'],
            bailleurOuSyndic: ['ACME'],
            zone: '5',
            natureParc: 'public',
            dossiersMultiples: 'oui',
            typesArretes: ['interdiction', 'peril'],
            page: 2,
        );

        $expectedFilters = [
            'territories' => ['13'],
            'adresse' => 'rue de la paix',
            'cities' => ['Marseille', 'Aix-en-Provence'],
            'bailleurOrSyndic' => ['ACME'],
            'zone' => '5',
            'housetypes' => [1],
            'dossiersMultiples' => 'oui',
            'typesArretes' => ['interdiction', 'peril'],
            'page' => 2,
            'maxItemsPerPage' => 25,
        ];

        $filters = $query->getFilters();
        static::assertSame($expectedFilters, $filters);

        $errors = $validator->validate($query);
        $this->assertCount(0, $errors);
    }

    public function testGetFiltersWithPrivateNatureParc(): void
    {
        $query = new AddressesHistorySearchQuery(
            natureParc: 'privee',
        );

        $filters = $query->getFilters();
        static::assertSame([0], $filters['housetypes']);
        static::assertSame(1, $filters['page']);
        static::assertSame(25, $filters['maxItemsPerPage']);
    }

    public function testGetFiltersWithNonRenseigneNatureParc(): void
    {
        $query = new AddressesHistorySearchQuery(
            natureParc: 'non_renseigne',
        );

        $filters = $query->getFilters();
        static::assertSame(['non_renseigne'], $filters['housetypes']);
    }

    public function testGetFiltersWithMinimalParameters(): void
    {
        $query = new AddressesHistorySearchQuery();

        $filters = $query->getFilters();
        static::assertSame(1, $filters['page']);
        static::assertSame(25, $filters['maxItemsPerPage']);
        static::assertCount(2, $filters);
    }

    public function testGetters(): void
    {
        $query = new AddressesHistorySearchQuery(
            territoire: '13',
            adresse: 'rue de la paix',
            communes: ['Marseille'],
            bailleurOuSyndic: ['ACME'],
            zone: '5',
            natureParc: 'public',
            dossiersMultiples: 'oui',
            typesArretes: ['interdiction'],
            page: 3,
        );

        static::assertSame('13', $query->getTerritoire());
        static::assertSame('rue de la paix', $query->getAdresse());
        static::assertSame(['Marseille'], $query->getCommunes());
        static::assertSame(['ACME'], $query->getBailleurOuSyndic());
        static::assertSame('5', $query->getZone());
        static::assertSame('public', $query->getNatureParc());
        static::assertSame('oui', $query->getDossiersMultiples());
        static::assertSame(['interdiction'], $query->getTypesArretes());
        static::assertSame(3, $query->getPage());
    }

    public function testGetQueryStringForUrlWithAllParameters(): void
    {
        $query = new AddressesHistorySearchQuery(
            territoire: '13',
            adresse: 'rue de la paix',
            communes: ['Marseille'],
            bailleurOuSyndic: ['ACME'],
            zone: '5',
            natureParc: 'public',
            dossiersMultiples: 'oui',
            typesArretes: ['interdiction'],
            page: 2,
        );

        $queryString = $query->getQueryStringForUrl();

        static::assertStringContainsString('territoire=13', $queryString);
        static::assertStringContainsString('adresse=rue+de+la+paix', $queryString);
        static::assertStringContainsString('bailleurOuSyndic%5B%5D=ACME', $queryString);
        static::assertStringContainsString('zone=5', $queryString);
        static::assertStringContainsString('natureParc=public', $queryString);
        static::assertStringContainsString('dossiersMultiples=oui', $queryString);
        static::assertStringContainsString('page=2', $queryString);
    }

    public function testGetQueryStringForUrlExcludesPageOne(): void
    {
        $query = new AddressesHistorySearchQuery(
            territoire: '13',
            page: 1,
        );

        $queryString = $query->getQueryStringForUrl();

        static::assertStringContainsString('territoire=13', $queryString);
        static::assertStringNotContainsString('page=', $queryString);
    }

    public function testGetQueryStringForUrlWithMinimalParameters(): void
    {
        $query = new AddressesHistorySearchQuery();

        $queryString = $query->getQueryStringForUrl();

        static::assertSame('', $queryString);
    }

    public function testFromParams(): void
    {
        $params = [
            'territoire' => '13',
            'adresse' => 'rue de la paix',
            'communes' => ['Marseille'],
            'bailleurOuSyndic' => ['ACME'],
            'zone' => '5',
            'natureParc' => 'public',
            'dossiersMultiples' => 'oui',
            'typesArretes' => ['interdiction'],
            'page' => '3',
        ];

        $query = AddressesHistorySearchQuery::fromParams($params);

        static::assertSame('13', $query->getTerritoire());
        static::assertSame('rue de la paix', $query->getAdresse());
        static::assertSame(['Marseille'], $query->getCommunes());
        static::assertSame(['ACME'], $query->getBailleurOuSyndic());
        static::assertSame('5', $query->getZone());
        static::assertSame('public', $query->getNatureParc());
        static::assertSame('oui', $query->getDossiersMultiples());
        static::assertSame(['interdiction'], $query->getTypesArretes());
        static::assertSame(3, $query->getPage());
    }

    public function testFromParamsWithEmptyArray(): void
    {
        $query = AddressesHistorySearchQuery::fromParams([]);

        static::assertNull($query->getTerritoire());
        static::assertNull($query->getAdresse());
        static::assertNull($query->getCommunes());
        static::assertNull($query->getBailleurOuSyndic());
        static::assertNull($query->getZone());
        static::assertNull($query->getNatureParc());
        static::assertNull($query->getDossiersMultiples());
        static::assertNull($query->getTypesArretes());
        static::assertSame(1, $query->getPage());
    }

    public function testFromParamsIgnoresInvalidCommunesType(): void
    {
        $params = [
            'communes' => 'not-an-array',
        ];

        $query = AddressesHistorySearchQuery::fromParams($params);

        static::assertNull($query->getCommunes());
    }

    public function testValidationFailsWithInvalidNatureParc(): void
    {
        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get('validator');

        $query = new AddressesHistorySearchQuery(
            natureParc: 'invalid',
        );

        $errors = $validator->validate($query);
        $this->assertCount(1, $errors);
        $this->assertSame('Nature du parc invalide', $errors[0]->getMessage());
    }

    public function testValidationFailsWithInvalidDossiersMultiples(): void
    {
        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get('validator');

        $query = new AddressesHistorySearchQuery(
            dossiersMultiples: 'invalid',
        );

        $errors = $validator->validate($query);
        $this->assertCount(1, $errors);
        $this->assertSame('Dossiers multiples invalide', $errors[0]->getMessage());
    }
}
