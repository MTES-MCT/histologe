<?php

namespace App\Tests\Unit\Messenger;

use App\Entity\Enum\PartnerType;
use App\Messenger\Message\DossierMessageSCHS;
use App\Messenger\Message\DossierMessageSISH;
use App\Service\Esabora\AbstractEsaboraService;
use App\Service\Esabora\Enum\PersonneType;
use App\Service\Esabora\Model\DossierMessageSISHPersonne;
use App\Utils\Enum\ExtensionAdresse;
use Faker\Factory;

trait DossierMessageTrait
{
    protected function getDossierMessageSCHS(): DossierMessageSCHS
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

    protected function getDossierMessageSISH(): DossierMessageSISH
    {
        $faker = Factory::create('fr_FR');
        $uuid = $faker->uuid();

        return (new DossierMessageSISH())
            ->setUrl($faker->url())
            ->setToken($faker->password(20))
            ->setPartnerId($faker->randomDigit())
            ->setPartnerType(PartnerType::ARS->name)
            ->setSignalementId($faker->randomDigit())
            ->setReferenceAdresse($uuid)
            ->setLocalisationNumero($faker->randomDigit())
            ->setLocalisationNumeroExt(ExtensionAdresse::BIS->name)
            ->setLocalisationAdresse1($faker->streetName())
            ->setLocalisationAdresse2(null)
            ->setLocalisationCodePostal($faker->postcode())
            ->setLocalisationVille($faker->city())
            ->setLocalisationLocalisationInsee($faker->postcode())
            ->setSasLogicielProvenance('H')
            ->setReferenceDossier($uuid)
            ->setSasDateAffectation('25/04/2023 15:01')
            ->setLocalisationEtage($faker->randomDigit())
            ->setLocalisationEscalier($faker->randomDigit())
            ->setLocalisationNumPorte($faker->randomDigit())
            ->setSitOccupantNbAdultes(3)
            ->setSitOccupantNbEnfantsM6(4)
            ->setSitOccupantNbEnfantsP6(2)
            ->setSitOccupantNbOccupants(9)
            ->setSitOccupantNumAllocataire('0000000')
            ->setSitOccupantMontantAlloc(100)
            ->setSitLogementBailEncours(1)
            ->setSitLogementBailDateEntree('10/01/2021')
            ->setSitLogementPreavisDepart(0)
            ->setSitLogementRelogement(0)
            ->setSitLogementSuperficie(70)
            ->setSitLogementMontantLoyer(900)
            ->setDeclarantNonOccupant(1)
            ->setLogementNature('Appartement')
            ->setLogementType('T3')
            ->setLogementSocial(0)
            ->setLogementAnneeConstruction(null)
            ->setLogementTypeEnergie(null)
            ->setLogementCollectif(0)
            ->setLogementAvant1949(0)
            ->setLogementDiagST(0)
            ->setLogementInvariant(null)
            ->setLogementNbPieces(4)
            ->setLogementNbChambres(2)
            ->setLogementNbNiveaux(1)
            ->setProprietaireAverti(0)
            ->setProprietaireAvertiDate('21/04/2023')
            ->setProprietaireAvertiMoyen('sms')
            ->setSignalementScore(50.2)
            ->setSignalementOrigine(AbstractEsaboraService::SIGNALEMENT_ORIGINE)
            ->setSignalementNumero('2023-52')
            ->setSignalementCommentaire($faker->realText)
            ->setSignalementDate('25/04/2023')
            ->setSignalementDetails($faker->realText)
            ->setSignalementProblemes($faker->text)
            ->setPiecesJointesObservation(null)
            ->setPiecesJointesDocuments([
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
            ])
            ->addPersonne($this->getDossierMessageSISHPersonne());
    }

    protected function getDossierMessageSISHPersonne(): DossierMessageSISHPersonne
    {
        $faker = Factory::create('fr_FR');

        return (new DossierMessageSISHPersonne())
            ->setType(PersonneType::OCCUPANT->value)
            ->setNom($faker->lastName())
            ->setPrenom($faker->firstName())
            ->setEmail($faker->email())
            ->setTelephone($faker->phoneNumber());
    }
}
