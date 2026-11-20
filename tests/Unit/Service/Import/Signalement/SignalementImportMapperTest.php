<?php

namespace App\Tests\Unit\Service\Import\Signalement;

use App\Service\Import\Signalement\SignalementImportMapper;
use PHPUnit\Framework\TestCase;

class SignalementImportMapperTest extends TestCase
{
    private SignalementImportMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new SignalementImportMapper();
    }

    public function testMapWithoutAnonymizeKeepsPersonalData(): void
    {
        $data = $this->buildRow($this->getPersonalData());

        $dataMapped = $this->mapper->map(array_keys($data), $data);

        $this->assertSame('Dupont', $dataMapped['nomOccupant']);
        $this->assertSame('jean.dupont@yopmail.com', $dataMapped['mailOccupant']);
        $this->assertSame('0612345678', $dataMapped['telOccupant']);
        $this->assertSame('123456', $dataMapped['numAllocataire']);
        $this->assertSame('Fuite au plafond', $dataMapped['details']);
    }

    public function testMapWithAnonymizeReplacesFilledPersonalData(): void
    {
        $data = $this->buildRow($this->getPersonalData());

        $dataMapped = $this->mapper->map(array_keys($data), $data, true);

        $textFields = [
            'details',
            'nomOccupant',
            'prenomOccupant',
            'nomProprio',
            'nomDeclarant',
            'prenomDeclarant',
            'structureDeclarant',
            'naissanceOccupants',
            'conclusionVisite',
        ];
        foreach ($textFields as $field) {
            $this->assertStringStartsWith($field.'_anonymized_', $dataMapped[$field], $field);
        }

        foreach (['mailOccupant', 'mailProprio', 'mailDeclarant'] as $field) {
            $this->assertStringStartsWith($field.'_anonymized_', $dataMapped[$field], $field);
            $this->assertStringEndsWith('@example.com', $dataMapped[$field], $field);
        }

        foreach (['telOccupant', 'telProprio', 'telDeclarant'] as $field) {
            $this->assertSame('0600000000', $dataMapped[$field], $field);
        }

        $this->assertSame(0, $dataMapped['numAllocataire']);
        $this->assertSame(0, $dataMapped['montantAllocation']);

        // Les valeurs anonymisées ne doivent pas entrer en collision
        $this->assertNotSame(
            $this->mapper->map(array_keys($data), $data, true)['mailOccupant'],
            $dataMapped['mailOccupant']
        );
    }

    public function testMapWithAnonymizeKeepsEmptyFieldsEmpty(): void
    {
        $data = $this->buildRow();

        $dataMapped = $this->mapper->map(array_keys($data), $data, true);

        $fields = [
            'details',
            'numAllocataire',
            'nomOccupant',
            'prenomOccupant',
            'telOccupant',
            'mailOccupant',
            'nomProprio',
            'telProprio',
            'mailProprio',
            'nomDeclarant',
            'prenomDeclarant',
            'telDeclarant',
            'mailDeclarant',
            'structureDeclarant',
            'montantAllocation',
            'naissanceOccupants',
            'conclusionVisite',
        ];
        foreach ($fields as $field) {
            $this->assertSame('', $dataMapped[$field], $field);
        }
    }

    public function testMapWithAnonymizeKeepsNonPersonalData(): void
    {
        $data = $this->buildRow($this->getPersonalData());

        $dataMapped = $this->mapper->map(array_keys($data), $data, true);

        $this->assertSame('2023-42', $dataMapped['reference']);
        $this->assertSame('13001', $dataMapped['cpOccupant']);
        $this->assertSame('Marseille', $dataMapped['villeOccupant']);
        $this->assertTrue($dataMapped['isAllocataire']);
        $this->assertSame('2', $dataMapped['nbAdultes']);
    }

    /**
     * @return array<string, string>
     */
    private function getPersonalData(): array
    {
        return [
            'details' => 'Fuite au plafond',
            'Allocataire' => 'O',
            'numéro Allocataire' => '123456',
            'montant allocation' => '250',
            'nb d\'adultes' => '2',
            'Nom propriétaire' => 'Martin',
            'Telephone Proprietaire' => '0611111111',
            'Mail Propriétaire' => 'proprio@yopmail.com',
            'nom du declarant' => 'Durand',
            'prenom declarant' => 'Paul',
            'telephone declarant' => '0622222222',
            'mail declarant' => 'declarant@yopmail.com',
            'nom structure declarant si tiers professionnel' => 'Association',
            'nom occupant' => 'Dupont',
            'prenom occupant' => 'Jean',
            'telephone occupant' => '0612345678',
            'mail occupant' => 'jean.dupont@yopmail.com',
            'Code postal occupant' => '13001',
            'ville occupant' => 'Marseille',
            'conclusion visite' => 'Logement insalubre',
            'naissance occupant' => '01/01/1980',
        ];
    }

    /**
     * Construit une ligne CSV contenant toutes les colonnes connues du mapping.
     *
     * @param array<string, string> $values
     *
     * @return array<string, string>
     */
    private function buildRow(array $values = []): array
    {
        $row = array_fill_keys(array_keys($this->mapper->getMapping()), '');
        $row['Ref signalement'] = '2023-42';
        $row['Date de creation signalement'] = '15/03/2023';

        return array_merge($row, $values);
    }
}
