<?php

namespace App\Tests\Functional\Manager;

use App\Entity\DesordreCategorie;
use App\Entity\DesordreCritere;
use App\Manager\DesordreCritereManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreCritereManagerTest extends KernelTestCase
{
    protected ManagerRegistry $managerRegistry;
    protected DesordreCritereManager $desordreCritereManager;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->desordreCritereManager = new DesordreCritereManager(
            $this->managerRegistry,
            DesordreCritere::class,
        );
    }

    public function testCreateOrUpdateDesordreCritere()
    {
        $desordreCategorie = new DesordreCategorie();
        $desordreCategorie->setLabel('Décoration intérieure et bon goût');
        $this->entityManager->persist($desordreCategorie);

        $desordreCritere = $this->desordreCritereManager->createOrUpdate(
            'desordre_logement_rideaux_absents',
            [
                'slugCategorie' => 'desordre_logement_decoration',
                'labelCategorie' => 'Décoration intérieure',
                'zoneCategorie' => 'Logement',
                'labelCritere' => 'Il n\'y a pas de rideaux aux fenêtres',
                'desordreCategorie' => $desordreCategorie,
            ]
        );

        $this->assertEquals($desordreCritere->getSlugCritere(), 'desordre_logement_rideaux_absents');
        $this->assertEquals($desordreCritere->getLabelCritere(), 'Il n\'y a pas de rideaux aux fenêtres');

        $desordreCritere = $this->desordreCritereManager->createOrUpdate(
            'desordre_logement_rideaux_absents',
            [
                'slugCategorie' => 'desordre_logement_decoration',
                'labelCategorie' => 'Décoration intérieure',
                'zoneCategorie' => 'Logement',
                'labelCritere' => 'Il n\'y a pas de rideaux aux fenêtres ou ils sont moches',
                'desordreCategorie' => $desordreCategorie,
            ]
        );
        $this->assertEquals($desordreCritere->getSlugCritere(), 'desordre_logement_rideaux_absents');
        $this->assertEquals(
            $desordreCritere->getLabelCritere(),
            'Il n\'y a pas de rideaux aux fenêtres ou ils sont moches'
        );
    }
}
