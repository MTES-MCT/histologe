<?php

namespace App\Tests\Functional\Service\Signalement\DesordreTraitement;

use App\Entity\DesordrePrecision;
use App\Repository\DesordrePrecisionRepository;
use App\Service\Signalement\DesordreTraitement\DesordreBatimentSecuriteMursFissures;
use App\Service\Signalement\DesordreTraitement\DesordreTraitementOuiNon;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreBatimentSecuriteMursFissuresTest extends KernelTestCase
{
    private DesordreTraitementOuiNon $desordreTraitementOuiNon;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->desordreTraitementOuiNon = static::getContainer()->get(DesordreTraitementOuiNon::class);
    }

    public function testFindDesordresPrecisionsBy(): void
    {
        /** @var DesordrePrecisionRepository $desordrePrecisionRepository */
        $desordrePrecisionRepository = $this->entityManager->getRepository(DesordrePrecision::class);

        $payload = json_decode(
            (string) file_get_contents(__DIR__.'../../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire_all_in.json'),
            true
        );

        /** @var array<DesordrePrecision> $precisions */
        $precisions = (new DesordreBatimentSecuriteMursFissures($desordrePrecisionRepository, $this->desordreTraitementOuiNon))->findDesordresPrecisionsBy(
            $payload,
            'desordres_batiment_securite_murs_fissures'
        );

        $this->assertEquals(1, \count($precisions));

        /** @var DesordrePrecision $precision */
        $precision = $precisions[0];
        $this->assertEquals(
            'desordres_batiment_securite_murs_fissures_details_mur_porteur_oui',
            $precision->getDesordrePrecisionSlug()
        );
        $this->assertTrue($precision->getIsDanger());
        $this->assertFalse($precision->getIsSuroccupation());
    }
}
