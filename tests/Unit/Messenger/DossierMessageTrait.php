<?php

namespace App\Tests\Unit\Messenger;

use App\Messenger\Message\DossierMessageSCHS;
use Faker\Factory;

trait DossierMessageTrait
{
    protected function getDossierMessage(): DossierMessageSCHS
    {
        $faker = Factory::create();

        return (new DossierMessageSCHS())
            ->setUrl($faker->url())
            ->setToken($faker->password(20))
            ->setPartnerId($faker->randomDigit())
            ->setSignalementId($faker->randomDigit())
            ->setReference($faker->uuid())
            ->setNomUsager($faker->lastName())
            ->setPrenomUsager($faker->firstName())
            ->setMailUsager($faker->email())
            ->setTelephoneUsager($faker->phoneNumber())
            ->setAdresseSignalement($faker->address())
            ->setCodepostaleSignalement($faker->postcode())
            ->setVilleSignalement($faker->city())
            ->setEtageSignalement('1')
            ->setNumeroAppartementSignalement('2')
            ->setNumeroAdresseSignalement('10')
            ->setLatitudeSignalement(0)
            ->setLongitudeSignalement(0)
            ->setDateOuverture('01/01/2022')
            ->setDossierCommentaire(null)
            ->setPiecesJointesObservation(null)
            ->setPiecesJointes(
                [
                    [
                        'documentName' => 'file',
                        'documentSize' => 80,
                        'documentContent' => 'file.pdf',
                    ],
                    [
                        'documentName' => 'Image téléversée',
                        'documentSize' => 80,
                        'documentContent' => 'image.jpg',
                    ],
                ]
            );
    }
}
