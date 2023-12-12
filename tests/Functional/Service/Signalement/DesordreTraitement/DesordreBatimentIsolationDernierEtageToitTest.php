<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\DesordrePrecision;
use App\Service\Signalement\DesordreTraitement\DesordreBatimentIsolationDernierEtageToit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreBatimentIsolationDernierEtageToitTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testFindDesordresPrecisionsBy()
    {
        $desordrePrecisionRepository = $this->entityManager->getRepository(DesordrePrecision::class);

        $payload = json_decode(
            file_get_contents(__DIR__.'../../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire_all_in.json'),
            true
        );

        /** @var array $precisions */
        $precisions = (new DesordreBatimentIsolationDernierEtageToit($desordrePrecisionRepository))->findDesordresPrecisionsBy(
            $payload,
            'desordres_batiment_isolation_dernier_etage_toit'
        );

        $this->assertEquals(1, \count($precisions));

        /** @var DesordrePrecision $precision */
        $precision = $precisions[0];
        $this->assertEquals(
            'desordres_batiment_isolation_dernier_etage_toit_sous_toit_oui',
            $precision->getDesordrePrecisionSlug()
        );
        $this->assertFalse($precision->isIsDanger());
        $this->assertFalse($precision->isIsSuroccupation());
    }
}
