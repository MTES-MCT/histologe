<?php

namespace App\Tests\Functional\Manager;

use App\Entity\DesordreCategorie;
use App\Entity\DesordreCritere;
use App\Entity\DesordrePrecision;
use App\Entity\Enum\DesordreCritereZone;
use App\Entity\Enum\Qualification;
use App\Manager\DesordrePrecisionManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordrePrecisionManagerTest extends KernelTestCase
{
    protected ManagerRegistry $managerRegistry;
    protected DesordrePrecisionManager $desordrePrecisionManager;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->desordrePrecisionManager = new DesordrePrecisionManager(
            $this->managerRegistry,
            DesordrePrecision::class,
        );
    }

    public function testCreateOrUpdateDesordrePrecision()
    {
        $desordreCategorie = new DesordreCategorie();
        $desordreCategorie->setLabel('Décoration intérieure et bon goût');
        $this->entityManager->persist($desordreCategorie);

        $desordreCritere = new DesordreCritere();
        $desordreCritere->setLabelCategorie('Décoration intérieure');
        $desordreCritere->setSlugCategorie('desordre_logement_decoration');
        $desordreCritere->setZoneCategorie(DesordreCritereZone::tryFromLabel('Logement'));
        $desordreCritere->setLabelCritere('Décoration intérieure et bon goût');
        $desordreCritere->setSlugCritere('desordre_logement_rideaux_absents');
        $desordreCritere->setDesordreCategorie($desordreCategorie);
        $this->entityManager->persist($desordreCritere);

        $desordrePrecision = $this->desordrePrecisionManager->createOrUpdate(
            'desordre_logement_rideaux_absents_a_motif',
            [
                'coef' => '0,8',
                'danger' => 'Oui',
                'suroccupation' => '',
                'insalubrite' => '',
                'label' => 'Les rideaux ont des motifs du dessin animé Cars',
                'procedure' => 'Péril',
                'desordreCritere' => $desordreCritere,
            ]
        );

        $this->assertEquals($desordrePrecision->getCoef(), 0.8);
        $this->assertEquals($desordrePrecision->getIsDanger(), true);
        $this->assertEquals($desordrePrecision->getIsSuroccupation(), false);
        $this->assertEquals($desordrePrecision->getQualification(), [Qualification::MISE_EN_SECURITE_PERIL]);

        $desordrePrecision = $this->desordrePrecisionManager->createOrUpdate(
            'desordre_logement_rideaux_absents_a_motif',
            [
                'coef' => '0,8',
                'danger' => '',
                'suroccupation' => 'Oui',
                'insalubrite' => '',
                'label' => 'Les rideaux ont des motifs du dessin animé Cars et La Reine des Neiges mélangés',
                'procedure' => 'Péril',
                'desordreCritere' => $desordreCritere,
            ]
        );
        $this->assertEquals(
            $desordrePrecision->getDesordrePrecisionSlug(),
            'desordre_logement_rideaux_absents_a_motif'
        );
        $this->assertEquals($desordrePrecision->getIsDanger(), false);
        $this->assertEquals($desordrePrecision->getIsSuroccupation(), true);
    }
}
