<?php

namespace App\Tests\Functional\Service\Signalement\DesordreTraitement;

use App\Entity\DesordrePrecision;
use App\Repository\DesordrePrecisionRepository;
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

    public function testFindDesordresPrecisionsBy(): void
    {
        /** @var DesordrePrecisionRepository $desordrePrecisionRepository */
        $desordrePrecisionRepository = $this->entityManager->getRepository(DesordrePrecision::class);

        $payload = json_decode(
            file_get_contents(__DIR__.'../../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire_all_in.json'),
            true
        );

        /** @var array<DesordrePrecision> $precisions */
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
        $this->assertTrue($precision->getIsDanger());
        $this->assertFalse($precision->getIsSuroccupation());
    }
}
