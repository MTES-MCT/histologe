<?php

namespace App\Tests\Unit\Factory\Signalement;

use App\Entity\Model\TypeCompositionLogement;
use App\Factory\Signalement\TypeCompositionLogementFactory;
use PHPUnit\Framework\TestCase;

class TypeCompositionLogementFactoryTest extends TestCase
{
    public function testCreateFromArrayKeepsAllFieldsOfToArray(): void
    {
        $typeCompositionLogement = (new TypeCompositionLogement())
            ->setTypeLogementNature('appartement')
            ->setCompositionLogementPieceUnique('plusieurs_pieces')
            ->setCompositionLogementNbPieces('3')
            ->setBailDpeDpe('oui')
            ->setBailDpeClasseEnergetique('F')
            ->setDesordresLogementChauffageDetailsDpeConsoFinale('320')
            ->setDesordresLogementChauffageDetailsDpeConso('15000')
            ->setDesordresLogementChauffageDetailsDpeAnnee('before2023')
            ->setDesordresLogementChauffageDetailsDpeConsoVide('non');

        // conversion utilisée par le type Doctrine TypeCompositionLogementType (écriture puis lecture en base)
        $data = json_decode((string) json_encode($typeCompositionLogement->toArray()), true);
        $typeCompositionLogementFromArray = TypeCompositionLogementFactory::createFromArray($data);

        $this->assertEquals($typeCompositionLogement->toArray(), $typeCompositionLogementFromArray->toArray());
        $this->assertEquals('320', $typeCompositionLogementFromArray->getDesordresLogementChauffageDetailsDpeConsoFinale());
        $this->assertEquals('15000', $typeCompositionLogementFromArray->getDesordresLogementChauffageDetailsDpeConso());
        $this->assertEquals('before2023', $typeCompositionLogementFromArray->getDesordresLogementChauffageDetailsDpeAnnee());
        $this->assertEquals('non', $typeCompositionLogementFromArray->getDesordresLogementChauffageDetailsDpeConsoVide());
    }
}
