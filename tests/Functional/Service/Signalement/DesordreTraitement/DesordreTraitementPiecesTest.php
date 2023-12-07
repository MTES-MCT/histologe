<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\DesordrePrecision;
use App\Service\Signalement\DesordreTraitement\DesordreTraitementPieces;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreTraitementPiecesTest extends KernelTestCase
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
        $precisions = (new DesordreTraitementPieces($desordrePrecisionRepository))->process(
            $payload,
            'desordres_logement_securite_balcons'
        );

        $this->assertEquals(1, \count($precisions));

        /** @var DesordrePrecision $precision */
        $precision = $precisions->first();
        $this->assertEquals(
            'desordres_logement_securite_balcons_pieces_piece_a_vivre',
            $precision->getDesordrePrecisionSlug()
        );
        $this->assertTrue($precision->isIsDanger());
        $this->assertFalse($precision->isIsSuroccupation());
    }
}
