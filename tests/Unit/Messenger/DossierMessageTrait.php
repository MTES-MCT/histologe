<?php

namespace App\Tests\Unit\Messenger;

use App\Messenger\Message\DossierMessage;
use Faker\Factory;

trait DossierMessageTrait
{
    protected function getDossierMessage(): DossierMessage
    {
        $faker = Factory::create();

        return (new DossierMessage())
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

    protected function preparePushPayload(): array
    {
        $faker = Factory::create();

        return [
            [
                'fieldName' => 'Référence_Histologe',
                'fieldValue' => $faker->randomNumber(),
            ],
            [
                'fieldName' => 'Usager_Nom',
                'fieldValue' => $faker->lastName(),
            ],
            [
                'fieldName' => 'Usager_Prénom',
                'fieldValue' => $faker->firstName(),
            ],
            [
                'fieldName' => 'Usager_Mail',
                'fieldValue' => $faker->email(),
            ],
            [
                'fieldName' => 'Usager_Téléphone',
                'fieldValue' => $faker->phoneNumber(),
            ],
            [
                'fieldName' => 'Usager_Numéro',
                'fieldValue' => '',
            ],
            [
                'fieldName' => 'Usager_Nom_Rue',
                'fieldValue' => $faker->streetAddress(),
            ],
            [
                'fieldName' => 'Usager_Adresse2',
                'fieldValue' => '',
            ],
            [
                'fieldName' => 'Usager_CodePostal',
                'fieldValue' => $faker->postcode(),
            ],
            [
                'fieldName' => 'Usager_Ville',
                'fieldValue' => $faker->city(),
            ],
            [
                'fieldName' => 'Adresse_Numéro',
                'fieldValue' => $faker->buildingNumber(),
            ],
            [
                'fieldName' => 'Adresse_Nom_Rue',
                'fieldValue' => $faker->streetAddress(),
            ],
            [
                'fieldName' => 'Adresse_CodePostal',
                'fieldValue' => $faker->postcode(),
            ],
            [
                'fieldName' => 'Adresse_Ville',
                'fieldValue' => $faker->city(),
            ],
            [
                'fieldName' => 'Adresse_Etage',
                'fieldValue' => $faker->numberBetween(0, 10),
            ],
            [
                'fieldName' => 'Adresse_Porte',
                'fieldValue' => $faker->buildingNumber(),
            ],
            [
                'fieldName' => 'Adresse_Latitude',
                'fieldValue' => $faker->latitude(),
            ],
            [
                'fieldName' => 'Adresse_Longitude',
                'fieldValue' => $faker->longitude(),
            ],
            [
                'fieldName' => 'Dossier_Ouverture',
                'fieldValue' => $faker->date('d/m/Y'),
            ],
            [
                'fieldName' => 'Dossier_Commentaire',
                'fieldValue' => $faker->text(),
            ],
            [
                'fieldName' => 'PJ_Observations',
                'fieldValue' => $faker->filePath(),
            ],
            [
                'fieldName' => 'PJ_Documents',
                'fieldDocumentUpdate' => 1,
                'fieldValue' => [],
            ],
        ];
    }
}
