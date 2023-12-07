<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\DesordrePrecision;
use App\Service\Signalement\DesordreTraitement\DesordreLogementSecuritePlomb;
use App\Service\Signalement\DesordreTraitement\DesordreTraitementPieces;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreLogementSecuritePlombTest extends KernelTestCase
{
    private DesordreTraitementPieces $desordreTraitementPieces;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->desordreTraitementPieces = static::getContainer()->get(DesordreTraitementPieces::class);
    }

    public function testProcess()
    {
        $desordrePrecisionRepository = $this->entityManager->getRepository(DesordrePrecision::class);

        $payload = json_decode(
            file_get_contents(__DIR__.'../../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire_all_in.json'),
            true
        );

        /** @var ArrayCollection $precisions */
        $precisions = (new DesordreLogementSecuritePlomb($desordrePrecisionRepository, $this->desordreTraitementPieces))->process(
            $payload,
            'desordres_logement_securite_plomb'
        );

        $this->assertEquals(1, \count($precisions));

        /** @var DesordrePrecision $precision */
        $precision = $precisions->first();
        $this->assertEquals(
            'desordres_logement_securite_plomb_pieces_salle_de_bain_diagnostique_oui',
            $precision->getDesordrePrecisionSlug()
        );
        $this->assertTrue($precision->isIsDanger());
        $this->assertFalse($precision->isIsSuroccupation());
    }
}
