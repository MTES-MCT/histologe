<?php

namespace App\Tests\Unit\Service\Signalement\DesordreTraitement;

use App\Entity\DesordreCategorie;
use App\Entity\DesordreCritere;
use App\Service\Signalement\DesordreTraitement\DesordreLogementHumidite;
use App\Service\Signalement\DesordreTraitement\DesordreTraitementNuisibles;
use App\Service\Signalement\DesordreTraitement\DesordreTraitementProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class DesordreTraitementProcessorTest extends TestCase
{
    public function testProcess()
    {
        $desordreCategorie = new DesordreCategorie();
        $desordreCategorie->setLabel('test');
        $desordreCritere = new DesordreCritere();
        $desordreCritere->setSlugCritere('desordres_logement_nuisibles_cafards')
        ->setSlugCategorie('desordres_logement_nuisibles')
        ->setDesordreCategorie($desordreCategorie)
        ->setLabelCritere('test nuisibles cafards')
        ->setLabelCategorie('test nuisibles');

        $payload = json_decode(
            file_get_contents(__DIR__.'../../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire.json'),
            true
        );

        $desordreTraitementNuisibles = $this->createMock(DesordreTraitementNuisibles::class);

        $desordreTraitementNuisibles
         ->expects($this->once())
             ->method('process')
             ->with($payload, $desordreCritere->getSlugCritere());

        $desordreLogementHumidite = $this->createMock(DesordreLogementHumidite::class);

        $desordreLogementHumidite
            ->expects($this->never())
            ->method('process');

        $widgetLoaderCollection = new DesordreTraitementProcessor([
            'desordres_logement_nuisibles_cafards' => $desordreTraitementNuisibles,
            'desordres_logement_humidite' => $desordreLogementHumidite,
        ]);

        $widgetLoaderCollection->process(
            $desordreCritere,
            $payload
        );
    }

    public function testProcessKO()
    {
        $desordreCategorie = new DesordreCategorie();
        $desordreCategorie->setLabel('test');
        $desordreCritere = new DesordreCritere();
        $desordreCritere->setSlugCritere('blabla')
        ->setSlugCategorie('bla')
        ->setDesordreCategorie($desordreCategorie)
        ->setLabelCritere('test blfdsiufds')
        ->setLabelCategorie('test fdsoip');

        $payload = json_decode(
            file_get_contents(__DIR__.'../../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire.json'),
            true
        );

        $desordreTraitementNuisibles = $this->createMock(DesordreTraitementNuisibles::class);

        $desordreTraitementNuisibles
        ->expects($this->never())
        ->method('process');

        $desordreLogementHumidite = $this->createMock(DesordreLogementHumidite::class);

        $desordreLogementHumidite
            ->expects($this->never())
            ->method('process');

        $widgetLoaderCollection = new DesordreTraitementProcessor([
            'desordres_logement_nuisibles_cafards' => $desordreTraitementNuisibles,
            'desordres_logement_humidite' => $desordreLogementHumidite,
        ]);

        /** @var ArrayCollection $precisions */
        $precisions = $widgetLoaderCollection->process(
            $desordreCritere,
            $payload
        );

        $this->assertNull($precisions);
    }
}
