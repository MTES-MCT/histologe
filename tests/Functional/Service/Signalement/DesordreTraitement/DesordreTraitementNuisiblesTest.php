<?php

namespace App\Tests\Functional\Service\Signalement\DesordreTraitement;

use App\Entity\DesordrePrecision;
use App\Service\Signalement\DesordreTraitement\DesordreTraitementNuisibles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreTraitementNuisiblesTest extends KernelTestCase
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
            file_get_contents(__DIR__.'../../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire.json'),
            true
        );

        /** @var array $precisions */
        $precisions = (new DesordreTraitementNuisibles($desordrePrecisionRepository))->findDesordresPrecisionsBy(
            $payload,
            'desordres_logement_nuisibles_cafards'
        );

        $this->assertEquals(1, \count($precisions));

        /** @var DesordrePrecision $precision */
        $precision = $precisions[0];
        $this->assertEquals(
            'desordres_logement_nuisibles_cafards_details_date_after_movein',
            $precision->getDesordrePrecisionSlug()
        );
        $this->assertFalse($precision->isIsDanger());
        $this->assertFalse($precision->isIsSuroccupation());
    }
}
