<?php

namespace App\Tests\Functional\Service\Signalement\DesordreTraitement;

use App\Entity\DesordrePrecision;
use App\Repository\DesordrePrecisionRepository;
use App\Service\Signalement\DesordreTraitement\DesordreLogementElectriciteManquePrises;
use App\Service\Signalement\DesordreTraitement\DesordreTraitementOuiNon;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreLogementElectriciteManquePrisesTest extends KernelTestCase
{
    public DesordreTraitementOuiNon $desordreTraitementOuiNon;
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
            (string) file_get_contents(__DIR__.'../../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire.json'),
            true
        );

        /** @var array<DesordrePrecision> $precisions */
        $precisions = (new DesordreLogementElectriciteManquePrises($desordrePrecisionRepository, $this->desordreTraitementOuiNon))->findDesordresPrecisionsBy(
            $payload,
            'desordres_logement_electricite_manque_prises'
        );

        $this->assertEquals(1, \count($precisions));

        /** @var DesordrePrecision $precision */
        $precision = $precisions[0];
        $this->assertEquals(
            'desordres_logement_electricite_manque_prises_details_multiprises_oui',
            $precision->getDesordrePrecisionSlug()
        );
        $this->assertTrue($precision->getIsDanger());
        $this->assertFalse($precision->getIsSuroccupation());
    }
}
