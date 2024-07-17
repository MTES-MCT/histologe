<?php

namespace App\Tests\Unit\Dto\Request\Signalement;

use App\Dto\Request\Signalement\SignalementSearchQuery;
use App\Entity\Enum\SignalementStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SignalementSearchQueryTest extends KernelTestCase
{
    public function testGetFilters(): void
    {
        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');

        $query = new SignalementSearchQuery(
            '13',
            'John',
            'nouveau',
            ['Marseille'],
            ['244400503'],
            ['1', '5'],
            '2022-01-01',
            '2022-12-31',
            ['23'],
            'Non planifiée',
            'partenaire',
            '2022-01-01',
            '2023-01-01',
            'accepte',
            5,
            100,
            'locataire',
            'privee',
            'oui',
            'oui',
            'attente_relogement',
            'non_decence_energetique',
            1,
            'oui',
            'NO_SUIVI_AFTER_3_RELANCES',
            'oui',
            30,
            'reference',
            'DESC',
        );

        $expectedFilters = [
            'searchterms' => 'John',
            'territories' => ['13'],
            'statuses' => [SignalementStatus::mapFilterStatus('nouveau')],
            'cities' => ['Marseille'],
            'epcis' => ['244400503'],
            'partners' => ['23'],
            'allocs' => ['1', 'caf', 'msa'],
            'housetypes' => [0],
            'enfantsM6' => [1],
            'visites' => ['Non planifiée'],
            'scores' => [
                'on' => 5.0,
                'off' => 100.0,
            ],
            'dates' => [
                'on' => '2022-01-01',
                'off' => '2022-12-31',
            ],
            'tags' => ['1', '5'],
            'typeDeclarant' => 'LOCATAIRE',
            'situation' => 'attente_relogement',
            'procedure' => 'NON_DECENCE_ENERGETIQUE',
            'typeDernierSuivi' => 'partenaire',
            'datesDernierSuivi' => [
                'on' => '2022-01-01',
                'off' => '2023-01-01',
            ],
            'statusAffectation' => 'accepte',
            'isImported' => true,
            'relances_usager' => [
                'NO_SUIVI_AFTER_3_RELANCES',
            ],
            'delays' => 30,
            'nouveau_suivi' => 'oui',
            'page' => 1,
            'maxItemsPerPage' => 25,
            'sortBy' => 'reference',
            'orderBy' => 'DESC',
        ];

        static::assertSame($expectedFilters, $query->getFilters());
        $errors = $validator->validate($query);
        $this->assertCount(0, $errors);
    }
}
