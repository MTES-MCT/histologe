<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProprioType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\User;
use App\Entity\UserApiPermission;
use App\Repository\SignalementRepository;
use App\Tests\ApiHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementCreateControllerTest extends WebTestCase
{
    use ApiHelper;

    private KernelBrowser $client;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->router = self::getContainer()->get('router');
        $this->client->disableReboot();
    }

    public function testCreateSignalementWithSuccessOnFullPayloadAndAutoAffectation(): void
    {
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'api-34-01@signal-logement.fr']);
        $permissionParams = ['user' => $user, 'partnerType' => null, 'territory' => null];
        $partner = self::getContainer()->get('doctrine')->getRepository(UserApiPermission::class)->findOneBy($permissionParams)->getPartner();
        $this->client->loginUser($user, 'api');

        $payload = $this->getFullPayload();
        $payload['partenaireUuid'] = $partner->getUuid();

        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_create_post'),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $signalementUuuid = json_decode($this->client->getResponse()->getContent(), true)['uuid'];
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $signalementUuuid]);
        $this->assertNotNull($signalement);

        $typeCompositionLogement = $signalement->getTypeCompositionLogement();
        $informationComplementaire = $signalement->getInformationComplementaire();
        $situationFoyer = $signalement->getSituationFoyer();
        $informationProcedure = $signalement->getInformationProcedure();
        $jsonContent = [];

        // adresse BAN
        $this->assertEquals($signalement->getAdresseOccupant(), '151 Avenue du Pont Trinquat');
        $this->assertEquals($signalement->getCpOccupant(), $payload['codePostalOccupant']);
        $this->assertEquals($signalement->getVilleOccupant(), $payload['communeOccupant']);
        $this->assertEquals($signalement->getTerritory()->getZip(), 34);
        $this->assertEquals($signalement->getInseeOccupant(), 34172);
        $this->assertArrayHasKey('lat', $signalement->getGeoloc());
        $this->assertArrayHasKey('lng', $signalement->getGeoloc());
        $this->assertNotEmpty($signalement->getBanIdOccupant());
        // champs classiques
        $this->assertEquals($signalement->getEtageOccupant(), $payload['etageOccupant']);
        $this->assertEquals($signalement->getEscalierOccupant(), $payload['escalierOccupant']);
        $this->assertEquals($signalement->getNumAppartOccupant(), $payload['numAppartOccupant']);
        $this->assertEquals($signalement->getAdresseAutreOccupant(), $payload['adresseAutreOccupant']);
        $this->assertEquals($signalement->getProfileDeclarant(), ProfileDeclarant::from($payload['profilDeclarant']));
        $this->assertEquals($signalement->getLienDeclarantOccupant(), $payload['lienDeclarantOccupant']);
        $this->assertEquals($signalement->getIsLogementSocial(), $payload['isLogementSocial']);
        $this->assertEquals($signalement->getIsLogementVacant(), $payload['isLogementVacant']);
        $this->assertEquals($signalement->getNbOccupantsLogement(), $payload['nbOccupantsLogement']);
        $this->assertEquals($typeCompositionLogement->getCompositionLogementNombrePersonnes(), $payload['nbOccupantsLogement']);
        $this->assertEquals($typeCompositionLogement->getCompositionLogementNombreEnfants(), $payload['nbEnfantsDansLogement']);
        $this->assertEquals($typeCompositionLogement->getCompositionLogementEnfants(), $payload['isEnfantsMoinsSixAnsDansLogement']);
        $this->assertEquals($signalement->getNatureLogement(), $payload['natureLogement']);
        $this->assertEquals($typeCompositionLogement->getTypeLogementAppartementAvecFenetres(), 'oui');
        $this->assertEquals($typeCompositionLogement->getTypeLogementRdc(), 'oui');
        $this->assertEquals($typeCompositionLogement->getTypeLogementSousSolSansFenetre(), 'non');
        $this->assertEquals($typeCompositionLogement->getTypeLogementDernierEtage(), 'non');
        $this->assertEquals($typeCompositionLogement->getTypeLogementSousCombleSansFenetre(), 'non');
        $this->assertEquals($typeCompositionLogement->getTypeLogementSousSolSansFenetre(), 'non');
        $this->assertEquals($informationComplementaire->getInformationsComplementairesLogementNombreEtages(), $payload['nombreEtages']);
        $this->assertEquals($informationComplementaire->getInformationsComplementairesLogementAnneeConstruction(), $payload['anneeConstruction']);
        $this->assertEquals($typeCompositionLogement->getCompositionLogementPieceUnique(), 'plusieurs_pieces');
        $this->assertEquals($signalement->getSuperficie(), $payload['superficie']);
        $this->assertEquals($typeCompositionLogement->getTypeLogementCommoditesPieceAVivre9m(), 'oui');
        $this->assertEquals($typeCompositionLogement->getTypeLogementCommoditesCuisine(), 'oui');
        $this->assertEquals($typeCompositionLogement->getTypeLogementCommoditesSalleDeBain(), 'non');
        $this->assertEquals($typeCompositionLogement->getTypeLogementCommoditesWc(), 'non');
        $this->assertEquals($typeCompositionLogement->getTypeLogementCommoditesCuisineCollective(), null);
        $this->assertEquals($typeCompositionLogement->getTypeLogementCommoditesSalleDeBainCollective(), 'oui');
        $this->assertEquals($typeCompositionLogement->getTypeLogementCommoditesWcCuisine(), 'non');
        $jsonContent['desordres_logement_chauffage'] = $payload['typeChauffage'];
        $this->assertEquals($typeCompositionLogement->getBailDpeBail(), 'oui');
        $this->assertEquals($typeCompositionLogement->getBailDpeDpe(), 'oui');
        $this->assertEquals($typeCompositionLogement->getDesordresLogementChauffageDetailsDpeAnnee(), 'before2023');
        $this->assertEquals($typeCompositionLogement->getBailDpeClasseEnergetique(), $payload['classeEnergetique']);
        $this->assertEquals($typeCompositionLogement->getBailDpeEtatDesLieux(), 'oui');
        $this->assertEquals($typeCompositionLogement->getBailDpeDateEmmenagement(), $payload['dateEntreeLogement']);
        $this->assertEquals($informationComplementaire->getInformationsComplementairesLogementMontantLoyer(), $payload['montantLoyer']);
        $this->assertEquals($informationComplementaire->getInformationsComplementairesSituationOccupantsLoyersPayes(), 'oui');
        $this->assertEquals($signalement->getIsAllocataire(), 'caf');
        $this->assertEquals($signalement->getDateNaissanceOccupant()->format('Y-m-d'), $payload['dateNaissanceAllocataire']);
        $this->assertEquals($signalement->getNumAllocataire(), $payload['numAllocataire']);
        $this->assertEquals($informationComplementaire->getInformationsComplementairesSituationOccupantsTypeAllocation(), mb_strtolower($payload['typeAllocation']));
        $this->assertEquals($signalement->getMontantAllocation(), $payload['montantAllocation']);
        $this->assertEquals($situationFoyer->getTravailleurSocialAccompagnement(), 'oui');
        $this->assertEquals($situationFoyer->getTravailleurSocialAccompagnementNomStructure(), $payload['accompagnementTravailleurSocialNomStructure']);
        $this->assertEquals($informationComplementaire->getInformationsComplementairesSituationOccupantsBeneficiaireRsa(), 'non');
        $this->assertEquals($informationComplementaire->getInformationsComplementairesSituationOccupantsBeneficiaireFsl(), 'non');
        $this->assertEquals($signalement->getIsProprioAverti(), $payload['isBailleurAverti']);
        $this->assertEquals($signalement->getProprioAvertiAt()->format('Y-m-d'), $payload['dateBailleurAverti']);
        $this->assertEquals($informationProcedure->getInfoProcedureBailMoyen(), $payload['moyenInformationBailleur']);
        $this->assertEquals($informationProcedure->getInfoProcedureBailReponse(), $payload['reponseBailleur']);
        $this->assertEquals($signalement->getIsRelogement(), $payload['isDemandeRelogement']);
        $this->assertEquals($situationFoyer->getTravailleurSocialQuitteLogement(), 'non');
        $this->assertEquals($situationFoyer->getTravailleurSocialPreavisDepart(), 'non');
        $this->assertEquals($signalement->getIsPreavisDepart(), $payload['isPreavisDepartDepose']);
        $this->assertEquals($informationProcedure->getInfoProcedureAssuranceContactee(), 'oui');
        $this->assertEquals($informationProcedure->getInfoProcedureReponseAssurance(), $payload['reponseAssurance']);
        $this->assertEquals($signalement->getCiviliteOccupant(), mb_strtolower($payload['civiliteOccupant']));
        $this->assertEquals($signalement->getNomOccupant(), $payload['nomOccupant']);
        $this->assertEquals($signalement->getPrenomOccupant(), $payload['prenomOccupant']);
        $this->assertEquals($signalement->getMailOccupant(), $payload['mailOccupant']);
        $this->assertEquals($signalement->getTelOccupant(), $payload['telOccupant']);
        $this->assertEquals($signalement->getTypeProprio(), ProprioType::from($payload['typeBailleur']));
        $this->assertEquals($signalement->getDenominationProprio(), null);
        $this->assertEquals($signalement->getNomProprio(), $payload['nomBailleur']);
        $this->assertEquals($signalement->getPrenomProprio(), $payload['prenomBailleur']);
        $this->assertEquals($signalement->getMailProprio(), $payload['mailBailleur']);
        $this->assertEquals($signalement->getTelProprio(), $payload['telBailleur']);
        $this->assertEquals($signalement->getAdresseProprio(), $payload['adresseBailleur']);
        $this->assertEquals($signalement->getCodePostalProprio(), $payload['codePostalBailleur']);
        $this->assertEquals($signalement->getVilleProprio(), $payload['communeBailleur']);
        $this->assertEquals($signalement->getIsNotOccupant(), true);
        $this->assertEquals($signalement->getStructureDeclarant(), $payload['structureDeclarant']);
        $this->assertEquals($signalement->getNomDeclarant(), $payload['nomDeclarant']);
        $this->assertEquals($signalement->getPrenomDeclarant(), $payload['prenomDeclarant']);
        $this->assertEquals($signalement->getMailDeclarant(), $payload['mailDeclarant']);
        $this->assertEquals($signalement->getTelDeclarant(), $payload['telDeclarant']);
        $this->assertEquals($signalement->getDenominationAgence(), $payload['denominationAgence']);
        $this->assertEquals($signalement->getNomAgence(), $payload['nomAgence']);
        $this->assertEquals($signalement->getPrenomAgence(), $payload['prenomAgence']);
        $this->assertEquals($signalement->getMailAgence(), $payload['mailAgence']);
        $this->assertEquals($signalement->getTelAgence(), $payload['telAgence']);
        // desordres
        $this->assertCount(4, $signalement->getDesordrePrecisions());
        $expectedSlugs = [
            'desordres_logement_humidite_salle_de_bain_details_machine_non',
            'desordres_logement_humidite_salle_de_bain_details_moisissure_apres_nettoyage_oui',
            'desordres_logement_humidite_salle_de_bain_details_fuite_non',
            'desordres_batiment_nuisibles_autres',
        ];
        foreach ($signalement->getDesordrePrecisions() as $desordrePrecision) {
            $this->assertContains($desordrePrecision->getDesordrePrecisionSlug(), $expectedSlugs);
        }
        $jsonContent['desordres_batiment_nuisibles_autres'] = 'Invasion de fourmis.';
        $this->assertEquals($signalement->getJsonContent(), $jsonContent);
        // auto affectation
        $this->assertEquals($signalement->getStatut(), SignalementStatus::ACTIVE);
        $this->assertCount(2, $signalement->getAffectations());
        // occupant, declarant + deux affectations = 4 mails
        $this->assertEmailCount(4);
        $this->assertEquals($signalement->getCreatedBy()->getId(), $user->getId());
        $this->assertEquals($signalement->getSignalementUsager()->getOccupant()->getEmail(), $payload['mailOccupant']);
        $this->assertEquals($signalement->getSignalementUsager()->getDeclarant()->getEmail(), $payload['mailDeclarant']);

        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function testCreateSignalementWithSuccessOnMinmimalPayloadWithoutAutoAffectation(): void
    {
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'api-02@signal-logement.fr']);
        $this->client->loginUser($user, 'api');
        $payload = $this->getMinimalPayload();

        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_create_post'),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $signalementUuuid = json_decode($this->client->getResponse()->getContent(), true)['uuid'];
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $signalementUuuid]);
        $this->assertNotNull($signalement);

        $this->assertEquals($signalement->getAdresseOccupant(), $payload['adresseOccupant']);
        $this->assertEquals($signalement->getCpOccupant(), $payload['codePostalOccupant']);
        $this->assertEquals($signalement->getVilleOccupant(), $payload['communeOccupant']);
        $this->assertEquals($signalement->getMailOccupant(), $payload['mailOccupant']);
        $this->assertEquals($signalement->getTerritory()->getZip(), 30);
        $this->assertEquals($signalement->getProfileDeclarant(), ProfileDeclarant::from($payload['profilDeclarant']));
        $this->assertEquals($signalement->getIsLogementSocial(), $payload['isLogementSocial']);
        $this->assertEquals($signalement->getIsLogementVacant(), false);
        $this->assertEquals($signalement->getNomOccupant(), $payload['nomOccupant']);
        $this->assertEquals($signalement->getPrenomOccupant(), $payload['prenomOccupant']);
        $this->assertEquals($signalement->getMailOccupant(), $payload['mailOccupant']);
        $this->assertCount(1, $signalement->getDesordrePrecisions());
        $this->assertEquals($signalement->getdesordrePrecisions()[0]->getDesordrePrecisionSlug(), 'desordres_logement_securite_sol_dangereux_pieces_tout');
        $this->assertEquals($signalement->getStatut(), SignalementStatus::NEED_VALIDATION);
        $this->assertCount(0, $signalement->getAffectations());
        $this->assertEmailCount(0);
        $this->assertEquals($signalement->getCreatedBy()->getId(), $user->getId());
        $this->assertEquals($signalement->getSignalementUsager()->getOccupant()->getEmail(), $payload['mailOccupant']);
        $this->assertNull($signalement->getSignalementUsager()->getDeclarant());

        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function testCreateDuplicatedSignalement(): void
    {
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'api-34-01@signal-logement.fr']);
        $permissionParams = ['user' => $user, 'partnerType' => null, 'territory' => null];
        $partner = self::getContainer()->get('doctrine')->getRepository(UserApiPermission::class)->findOneBy($permissionParams)->getPartner();
        $this->client->loginUser($user, 'api');

        $payload = $this->getFullPayload();
        $payload['partenaireUuid'] = $partner->getUuid();
        $payload['adresseOccupant'] = '240 Avenue Victor Hugo';
        $payload['codePostalOccupant'] = '34300';
        $payload['communeOccupant'] = 'Agde';
        $payload['nomOccupant'] = 'Brassens';

        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_create_post'),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['errors']);
        $this->assertStringStartsWith('Un signalement existe déjà à cette adresse', $response['errors'][0]['message']);

        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function testCreateSignalementOnBadTerritory(): void
    {
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'api-02@signal-logement.fr']);
        $this->client->loginUser($user, 'api');
        $payload = $this->getFullPayload();
        unset($payload['partenaireUuid']);

        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_create_post'),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['errors']);
        $this->assertStringStartsWith('Vous n\'avez pas le droit de créer un signalement sur le territoire', $response['errors'][0]['message']);

        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function testCreateSignalementOnUnexistingPartner(): void
    {
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'api-34-01@signal-logement.fr']);
        $this->client->loginUser($user, 'api');

        $payload = $this->getFullPayload();

        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_create_post'),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringStartsWith('Le partenaire n\'existe pas', $response['message']);

        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function testCreateSignalementWithInvalidFields(): void
    {
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'api-34-01@signal-logement.fr']);
        $permissionParams = ['user' => $user, 'partnerType' => null, 'territory' => null];
        $partner = self::getContainer()->get('doctrine')->getRepository(UserApiPermission::class)->findOneBy($permissionParams)->getPartner();
        $this->client->loginUser($user, 'api');

        $payload = $this->getFullPayload();
        $payload['partenaireUuid'] = $partner->getUuid();
        unset($payload['adresseOccupant']);
        $payload['profilDeclarant'] = 'INVALID_VALUE';
        $payload['telOccupant'] = '12345';
        $payload['mailDeclarant'] = 'invalid-mail';

        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_create_post'),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(5, $response['errors']);
        $errors = json_encode($response['errors']);
        $this->assertStringContainsString('Veuillez renseigner l\'adresse du logement.', $errors);
        $this->assertStringContainsString('Cette valeur doit \u00eatre l\'un des choix propos\u00e9s.', $errors);
        $this->assertStringContainsString('Le num\u00e9ro de t\u00e9l\u00e9phone \"12345\" n\'est pas au bon format.', $errors);
        $this->assertStringContainsString('L\'adresse e-mail du d\u00e9clarant n\'est pas valide.', $errors);

        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function testCreateSignalementWithInvalidDesordres(): void
    {
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'api-34-01@signal-logement.fr']);
        $permissionParams = ['user' => $user, 'partnerType' => null, 'territory' => null];
        $partner = self::getContainer()->get('doctrine')->getRepository(UserApiPermission::class)->findOneBy($permissionParams)->getPartner();
        $this->client->loginUser($user, 'api');

        $payload = $this->getFullPayload();
        $payload['partenaireUuid'] = $partner->getUuid();
        $payload['desordres'][0]['precisions'] = [];

        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_create_post'),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['errors']);
        $errors = json_encode($response['errors']);
        $this->assertStringContainsString('Au moins une pr\u00e9cision doit \u00eatre fournie pour le d\u00e9sordre \" desordres_logement_humidite_salle_de_bain \"', $errors);

        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    /**
     * @return array<string, mixed>
     */
    private function getFullPayload(): array
    {
        $payload = '
        {
            "partenaireUuid": "85401893-8d92-11f0-8aa8-f6901f1203f4",
            "adresseOccupant": "151 avenue du pont trinquat",
            "codePostalOccupant": "34070",
            "communeOccupant": "Montpellier",
            "etageOccupant": "2",
            "escalierOccupant": "B",
            "numAppartOccupant": "24B",
            "adresseAutreOccupant": "Résidence les oliviers",
            "profilDeclarant": "TIERS_PARTICULIER",
            "lienDeclarantOccupant": "PROCHE",
            "isLogementSocial": false,
            "isLogementVacant": false,
            "nbOccupantsLogement": 4,
            "nbEnfantsDansLogement": 2,
            "isEnfantsMoinsSixAnsDansLogement": true,
            "natureLogement": "appartement",
            "natureLogementAutre": null,
            "etageAppartement": "RDC",
            "isAppartementAvecFenetres": true,
            "nombreEtages": 0,
            "anneeConstruction": 1970,
            "nombrePieces": 4,
            "superficie": 85.5,
            "isPieceAVivre9m": true,
            "isCuisine": true,
            "isCuisineCollective": null,
            "isSalleDeBain": false,
            "isSalleDeBainCollective": true,
            "isWc": false,
            "isWcCollectif": false,
            "isWcCuisineMemePiece": false,
            "typeChauffage": "ELECTRIQUE",
            "isBail": true,
            "isDpe": true,
            "anneeDpe": "2021",
            "classeEnergetique": "D",
            "isEtatDesLieux": true,
            "dateEntreeLogement": "2018-06-01",
            "montantLoyer": 765.5,
            "isPaiementLoyersAJour": true,
            "isAllocataire": true,
            "caisseAllocations": "CAF",
            "dateNaissanceAllocataire": "2001-03-15",
            "numAllocataire": "1234567890",
            "typeAllocation": "APL",
            "montantAllocation": 250.75,
            "isAccompagnementTravailleurSocial": true,
            "accompagnementTravailleurSocialNomStructure": "CCAS de Montpellier",
            "isBeneficiaireRsa": false,
            "isBeneficiaireFsl": false,
            "isBailleurAverti": true,
            "dateBailleurAverti": "2025-02-01",
            "moyenInformationBailleur": "courrier",
            "reponseBailleur": "Le bailleur n\'a pas donné suite.",
            "isDemandeRelogement": false,
            "isSouhaiteQuitterLogement": false,
            "isPreavisDepartDepose": false,
            "isLogementAssure": true,
            "isAssuranceContactee": true,
            "reponseAssurance": "L\'assurance refuse de couvrir les dégâts.",
            "civiliteOccupant": "Mme",
            "nomOccupant": "Dupont",
            "prenomOccupant": "Marie",
            "mailOccupant": "marie.dupont@example.com",
            "telOccupant": "0639987654",
            "typeBailleur": "PARTICULIER",
            "denominationBailleur": null,
            "nomBailleur": "Vignon",
            "prenomBailleur": "René",
            "mailBailleur": "rene.vignon@example.com",
            "telBailleur": "0639980851",
            "adresseBailleur": "12 avenue des bartas",
            "codePostalBailleur": "34000",
            "communeBailleur": "Montpellier",
            "structureDeclarant": null,
            "nomDeclarant": "El Allali",
            "prenomDeclarant": "Hakim",
            "mailDeclarant": "el-allali.hakim@example.com",
            "telDeclarant": "0639980906",
            "denominationAgence": "IMMO 3600",
            "nomAgence": "Apollo-Sanchez",
            "prenomAgence": "Victoria",
            "mailAgence": "victoria.apollo@immo3600.com",
            "telAgence": "0639988821",
            "desordres": [
                {
                "identifiant": "desordres_logement_humidite_salle_de_bain",
                "precisions": [
                    "desordres_logement_humidite_salle_de_bain_details_machine_non",
                    "desordres_logement_humidite_salle_de_bain_details_moisissure_apres_nettoyage_oui",
                    "desordres_logement_humidite_salle_de_bain_details_fuite_non"
                ],
                "precisionLibres": []
                },
                {
                "identifiant": "desordres_batiment_nuisibles_autres",
                "precisions": [],
                "precisionLibres": [
                    {
                    "identifiant": "desordres_batiment_nuisibles_autres",
                    "description": "Invasion de fourmis."
                    }
                ]
                }
            ]
        }';

        return json_decode($payload, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function getMinimalPayload(): array
    {
        $payload = '
        {
        "adresseOccupant": "Chemin du grand méchant loup",
        "codePostalOccupant": "30360",
        "communeOccupant": "Vézénobres",
        "profilDeclarant": "LOCATAIRE",
        "isLogementSocial": true,
        "nomOccupant": "Gluten_",
        "prenomOccupant": "Joey",
        "mailOccupant": "gnagnagna@blabla.org",
        "desordres": [
                {
                "identifiant": "desordres_logement_securite_sol_dangereux",
                "precisions": [
                    "desordres_logement_securite_sol_dangereux_pieces_tout"
                ],
                "precisionLibres": []
                }
            ]
        }';

        return json_decode($payload, true);
    }
}
