<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\DesordrePrecision;
use App\Service\Signalement\DesordreTraitement\DesordreLogementHumidite;
use App\Service\Signalement\DesordreTraitement\DesordreTraitementOuiNon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreLogementHumiditeTest extends KernelTestCase
{
    private DesordreTraitementOuiNon $desordreTraitementOuiNon;
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
            file_get_contents(__DIR__.'../../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire_all_in.json'),
            true
        );

        /** @var ArrayCollection $precisions */
        $precisions = (new DesordreLogementHumidite($desordrePrecisionRepository, $this->desordreTraitementOuiNon))->process(
            $payload,
            'desordres_logement_humidite_piece_a_vivre'
        );

        $this->assertEquals(3, \count($precisions));

        /** @var DesordrePrecision $precision */
        $precision = $precisions->first();
        $this->assertEquals(
            'desordres_logement_humidite_piece_a_vivre_details_machine_non',
            $precision->getDesordrePrecisionSlug()
        );
        $precision = $precisions->next();
        $this->assertEquals(
            'desordres_logement_humidite_piece_a_vivre_details_fuite_non',
            $precision->getDesordrePrecisionSlug()
        );
        $precision = $precisions->next();
        $this->assertEquals(
            'desordres_logement_humidite_piece_a_vivre_details_moisissure_apres_nettoyage_oui',
            $precision->getDesordrePrecisionSlug()
        );
    }
}
