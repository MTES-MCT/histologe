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
            territoire: '13',
            searchTerms: 'John',
            status: 'nouveau',
            communes: ['Marseille'],
            epcis: ['244400503'],
            etiquettes: ['1', '5'],
            dateDepotDebut: '2022-01-01',
            dateDepotFin: '2022-12-31',
            partenaires: ['23'],
            bailleurSocial: null,
            visiteStatus: 'Non planifiée',
            typeDernierSuivi: 'partenaire',
            dateDernierSuiviDebut: '2022-01-01',
            dateDernierSuiviFin: '2023-01-01',
            statusAffectation: 'accepte',
            criticiteScoreMin: 5,
            criticiteScoreMax: 100,
            typeDeclarant: 'locataire',
            natureParc: 'privee',
            allocataire: 'oui',
            enfantsM6: 'oui',
            situation: 'attente_relogement',
            procedure: 'non_decence_energetique',
            procedureConstatee: 'non_decence',
            page: 1,
            isImported: 'oui',
            isZonesDisplayed: 'oui',
            relancesUsager: 'NO_SUIVI_AFTER_3_RELANCES',
            usagerAbandonProcedure: true,
            nouveauSuivi: 'oui',
            sansSuiviPeriode: 30,
            sortBy: 'reference',
            direction: 'DESC',
            createdFrom: 'formulaire-pro',
            relanceUsagerSansReponse: 'oui',
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
            'procedureConstatee' => 'NON_DECENCE',
            'typeDernierSuivi' => 'partenaire',
            'datesDernierSuivi' => [
                'on' => '2022-01-01',
                'off' => '2023-01-01',
            ],
            'statusAffectation' => 'accepte',
            'isImported' => true,
            'isZonesDisplayed' => true,
            'relances_usager' => [
                'NO_SUIVI_AFTER_3_RELANCES',
            ],
            'usager_abandon_procedure' => true,
            'delays' => 30,
            'nouveau_suivi' => 'oui',
            'createdFrom' => 'formulaire-pro',
            'relanceUsagerSansReponse' => true,
            'page' => 1,
            'maxItemsPerPage' => 25,
            'sortBy' => 'reference',
            'orderBy' => 'DESC',
        ];

        $filters = $query->getFilters();
        static::assertSame($expectedFilters, $filters);
        $errors = $validator->validate($query);
        $this->assertCount(0, $errors);
    }
}
