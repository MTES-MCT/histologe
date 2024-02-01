<?php

namespace App\Tests\Functional\Specification\Signalement;

use App\Entity\Model\SituationFoyer;
use App\Entity\Model\TypeCompositionLogement;
use App\Specification\Signalement\SuroccupationSpecification;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SuroccupationSpecificationTest extends KernelTestCase
{
    private SuroccupationSpecification $suroccupationSpecification;
    private SituationFoyer $situationFoyer;
    private TypeCompositionLogement $typeCompositionLogement;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->suroccupationSpecification = new SuroccupationSpecification();
        $this->situationFoyer = new SituationFoyer();
        $this->situationFoyer->setLogementSocialAllocation('oui');
        $this->typeCompositionLogement = new TypeCompositionLogement();
        $this->typeCompositionLogement->setCompositionLogementSuperficie('7');
        $this->typeCompositionLogement->setCompositionLogementNbPieces('1');
        $this->typeCompositionLogement->setCompositionLogementNombrePersonnes('1');
    }

    public function testCheckSuroccupationAllocataire1Personne(): void
    {
        $isSuroccupation = $this->suroccupationSpecification->isSatisfiedBy(
            $this->situationFoyer,
            $this->typeCompositionLogement
        );

        $this->assertTrue($isSuroccupation);
        $this->assertEquals(
            'desordres_type_composition_logement_suroccupation_allocataire',
            $this->suroccupationSpecification->getSlug()
        );
    }

    public function testCheckPasSuroccupationAllocataire1Personne(): void
    {
        $this->typeCompositionLogement->setCompositionLogementSuperficie('10');
        $isSuroccupation = $this->suroccupationSpecification->isSatisfiedBy(
            $this->situationFoyer,
            $this->typeCompositionLogement
        );

        $this->assertFalse($isSuroccupation);
    }

    public function testCheckSuroccupationAllocataire2Personnes(): void
    {
        $this->typeCompositionLogement->setCompositionLogementNombrePersonnes('2');
        $isSuroccupation = $this->suroccupationSpecification->isSatisfiedBy(
            $this->situationFoyer,
            $this->typeCompositionLogement
        );

        $this->assertTrue($isSuroccupation);
        $this->assertEquals(
            'desordres_type_composition_logement_suroccupation_allocataire',
            $this->suroccupationSpecification->getSlug()
        );
    }

    public function testCheckPasSuroccupationAllocataire2Personnes(): void
    {
        $this->typeCompositionLogement->setCompositionLogementNombrePersonnes('2');
        $this->typeCompositionLogement->setCompositionLogementSuperficie('17');
        $isSuroccupation = $this->suroccupationSpecification->isSatisfiedBy(
            $this->situationFoyer,
            $this->typeCompositionLogement
        );

        $this->assertFalse($isSuroccupation);
    }

    public function testCheckSuroccupationAllocataire3Personnes(): void
    {
        $this->typeCompositionLogement->setCompositionLogementNombrePersonnes('3');
        $this->typeCompositionLogement->setCompositionLogementSuperficie('23');
        $isSuroccupation = $this->suroccupationSpecification->isSatisfiedBy(
            $this->situationFoyer,
            $this->typeCompositionLogement
        );

        $this->assertTrue($isSuroccupation);
        $this->assertEquals(
            'desordres_type_composition_logement_suroccupation_allocataire',
            $this->suroccupationSpecification->getSlug()
        );
    }

    public function testCheckPasSuroccupationAllocataire3Personnes(): void
    {
        $this->typeCompositionLogement->setCompositionLogementNombrePersonnes('3');
        $this->typeCompositionLogement->setCompositionLogementSuperficie('26');
        $isSuroccupation = $this->suroccupationSpecification->isSatisfiedBy(
            $this->situationFoyer,
            $this->typeCompositionLogement
        );

        $this->assertFalse($isSuroccupation);
    }

    public function testCheckSuroccupationPasAllocataire3Personnes(): void
    {
        $this->situationFoyer->setLogementSocialAllocation('non');
        $this->typeCompositionLogement->setCompositionLogementNombrePersonnes('3');
        $isSuroccupation = $this->suroccupationSpecification->isSatisfiedBy(
            $this->situationFoyer,
            $this->typeCompositionLogement
        );

        $this->assertTrue($isSuroccupation);
        $this->assertEquals(
            'desordres_type_composition_logement_suroccupation_non_allocataire',
            $this->suroccupationSpecification->getSlug()
        );
    }

    public function testCheckPasSuroccupationPasAllocataire3Personnes(): void
    {
        $this->situationFoyer->setLogementSocialAllocation('non');
        $this->typeCompositionLogement->setCompositionLogementNombrePersonnes('3');
        $this->typeCompositionLogement->setCompositionLogementNbPieces('2');
        $isSuroccupation = $this->suroccupationSpecification->isSatisfiedBy(
            $this->situationFoyer,
            $this->typeCompositionLogement
        );

        $this->assertFalse($isSuroccupation);
    }

    public function testCheckSuroccupationAllocataireNull5Personnes(): void
    {
        $this->situationFoyer->setLogementSocialAllocation(null);
        $this->typeCompositionLogement->setCompositionLogementNombrePersonnes('5');
        $this->typeCompositionLogement->setCompositionLogementNbPieces('2');
        $isSuroccupation = $this->suroccupationSpecification->isSatisfiedBy(
            $this->situationFoyer,
            $this->typeCompositionLogement
        );

        $this->assertTrue($isSuroccupation);
        $this->assertEquals(
            'desordres_type_composition_logement_suroccupation_non_allocataire',
            $this->suroccupationSpecification->getSlug()
        );
    }

    public function testCheckPasSuroccupationAllocataireNull5Personnes(): void
    {
        $this->situationFoyer->setLogementSocialAllocation(null);
        $this->typeCompositionLogement->setCompositionLogementNombrePersonnes('5');
        $this->typeCompositionLogement->setCompositionLogementNbPieces('3');
        $isSuroccupation = $this->suroccupationSpecification->isSatisfiedBy(
            $this->situationFoyer,
            $this->typeCompositionLogement
        );

        $this->assertFalse($isSuroccupation);
    }

    public function testCheckSurrocupationNotPossibleWithSuperficieEqualNull(): void
    {
        $this->typeCompositionLogement->setCompositionLogementSuperficie(null);
        $isSuroccupation = $this->suroccupationSpecification->isSatisfiedBy(
            $this->situationFoyer,
            $this->typeCompositionLogement
        );
        $this->assertFalse($isSuroccupation, 'Reproductible case with tiers part., pro et service de secours');
    }
}
