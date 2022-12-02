<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Affectation;
use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Situation;
use App\Factory\DossierMessageFactory;
use App\Service\UploadHandlerService;
use Faker\Factory;
use PHPUnit\Framework\TestCase;

class DossierMessageFactoryTest extends TestCase
{
    public function testDossierMessageFactoryIsFullyCreated(): void
    {
        $faker = Factory::create('fr_FR');
        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->expects($this->exactly(2))
            ->method('getTmpFilepath')
            ->willReturn(__DIR__.'/../../../src/DataFixtures/sample.png');

        $criticite = (new Criticite())
            ->setCritere(
                (new Critere())
                    ->setLabel('critere')
                    ->setDescription('description critere')
                    ->setSituation(
                        (new Situation())
                            ->setLabel('situation')
                            ->setMenuLabel('menu-situation')
                    ))
            ->setLabel('criticite')
            ->setScore(2);

        $signalement = (new Signalement())
            ->addCriticite($criticite)
            ->setIsProprioAverti(false)
            ->setNbAdultes(2)
            ->setNbEnfantsP6(1)
            ->setNbEnfantsM6(1)
            ->setTelOccupant($faker->phoneNumber())
            ->setAdresseOccupant($faker->address())
            ->setEtageOccupant(2)
            ->setVilleOccupant($faker->city())
            ->setCpOccupant($faker->postcode())
            ->setNumAppartOccupant(2)
            ->setNomOccupant($faker->lastName())
            ->setPrenomOccupant($faker->firstName())
            ->setDocuments([
                [
                    'file' => __DIR__.'/../../../src/DataFixtures/sample.png',
                    'titre' => 'Doc',
                    'date' => '02.12.2022', ],
            ])
            ->setPhotos([
                [
                    'file' => __DIR__.'/../../../src/DataFixtures/sample.png',
                    'titre' => 'Photo',
                    'date' => '02.12.2022',
                ],
            ]);

        $partner = (new Partner())
            ->setNom($faker->company())
            ->setEsaboraUrl($faker->url())
            ->setEsaboraToken($faker->password(20));

        $affectation = (new Affectation())
            ->setSignalement($signalement)
            ->setPartner($partner);

        $dossierMessageFactory = new DossierMessageFactory($uploadHandlerServiceMock);
        $dossierMessage = $dossierMessageFactory->createInstance($affectation);

        $this->assertEquals(2, \count($dossierMessage->getPiecesJointes()));
        $this->assertStringContainsString('Doc', $dossierMessage->getPiecesJointesObservation());
        $this->assertStringContainsString('Points signalés', $dossierMessage->getDossierCommentaire());
        $this->assertStringContainsString('Etat grave', $dossierMessage->getDossierCommentaire());
    }
}
