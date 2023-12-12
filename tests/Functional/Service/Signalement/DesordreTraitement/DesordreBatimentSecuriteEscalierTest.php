<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\DesordrePrecision;
use App\Service\Signalement\DesordreTraitement\DesordreBatimentSecuriteEscalier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreBatimentSecuriteEscalierTest extends KernelTestCase
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
        $precisions = (new DesordreBatimentSecuriteEscalier($desordrePrecisionRepository))->findDesordresPrecisionsBy(
            $payload,
            'desordres_batiment_securite_escalier'
        );

        $this->assertEquals(1, \count($precisions));

        /** @var DesordrePrecision $precision */
        $precision = $precisions[0];
        $this->assertEquals(
            'desordres_batiment_securite_escalier_details_dangereux',
            $precision->getDesordrePrecisionSlug()
        );
        $this->assertTrue($precision->isIsDanger());
        $this->assertFalse($precision->isIsSuroccupation());
    }
}
