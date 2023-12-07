<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\DesordrePrecision;
use App\Service\Signalement\DesordreTraitement\DesordreBatimentSecuriteSol;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreBatimentSecuriteSolTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testProcess()
    {
        $desordrePrecisionRepository = $this->entityManager->getRepository(DesordrePrecision::class);

        $payload = json_decode(
            file_get_contents(__DIR__.'../../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire_all_in.json'),
            true
        );

        /** @var ArrayCollection $precisions */
        $precisions = (new DesordreBatimentSecuriteSol($desordrePrecisionRepository))->process(
            $payload,
            'desordres_batiment_securite_sol'
        );

        $this->assertEquals(2, \count($precisions));

        /** @var DesordrePrecision $precision */
        $precision = $precisions->first();
        $this->assertEquals(
            'desordres_batiment_securite_sol_details_plancher_abime',
            $precision->getDesordrePrecisionSlug()
        );
        $this->assertTrue($precision->isIsDanger());
        $this->assertFalse($precision->isIsSuroccupation());

        /** @var DesordrePrecision $precision */
        $precision2 = $precisions->last();
        $this->assertEquals(
            'desordres_batiment_securite_sol_details_plancher_effondre',
            $precision2->getDesordrePrecisionSlug()
        );
        $this->assertTrue($precision2->isIsDanger());
        $this->assertFalse($precision2->isIsSuroccupation());
    }
}
