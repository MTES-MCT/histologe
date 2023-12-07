<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\DesordrePrecision;
use App\Service\Signalement\DesordreTraitement\DesordreLogementElectriciteManquePrises;
use App\Service\Signalement\DesordreTraitement\DesordreTraitementOuiNon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreLogementElectriciteManquePrisesTest extends KernelTestCase
{
    public DesordreTraitementOuiNon $desordreTraitementOuiNon;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->desordreTraitementOuiNon = static::getContainer()->get(DesordreTraitementOuiNon::class);
    }

    public function testProcess()
    {
        $desordrePrecisionRepository = $this->entityManager->getRepository(DesordrePrecision::class);

        $payload = json_decode(
            file_get_contents(__DIR__.'../../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire.json'),
            true
        );

        /** @var ArrayCollection $precisions */
        $precisions = (new DesordreLogementElectriciteManquePrises($desordrePrecisionRepository, $this->desordreTraitementOuiNon))->process(
            $payload,
            'desordres_logement_electricite_manque_prises'
        );

        $this->assertEquals(1, \count($precisions));

        /** @var DesordrePrecision $precision */
        $precision = $precisions->first();
        $this->assertEquals(
            'desordres_logement_electricite_manque_prises_details_multiprises_oui',
            $precision->getDesordrePrecisionSlug()
        );
        $this->assertTrue($precision->isIsDanger());
        $this->assertFalse($precision->isIsSuroccupation());
    }
}
