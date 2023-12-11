<?php

namespace App\Tests;

use App\Entity\Affectation;
use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\PartnerType;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Situation;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Messenger\Message\Esabora\DossierMessageSCHS;
use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Service\Esabora\AbstractEsaboraService;
use App\Service\Esabora\Enum\PersonneType;
use App\Service\Esabora\Model\DossierMessageSISHPersonne;
use App\Service\Esabora\Response\DossierArreteSISHCollectionResponse;
use App\Service\Esabora\Response\DossierPushSISHResponse;
use App\Service\Esabora\Response\DossierVisiteSISHCollectionResponse;
use App\Utils\Enum\ExtensionAdresse;
use Faker\Factory;
use Faker\Provider\Address;

trait FixturesHelper
{
    public function getAffectation(PartnerType $partnerType): Affectation
    {
        $faker = Factory::create();

        return (new Affectation())
            ->setPartner(
                (new Partner())
                    ->setEsaboraToken($faker->password(20))
                    ->setEsaboraUrl($faker->url())
                    ->setType($partnerType)
            )->setSignalement(
                (new Signalement())
                    ->setUuid($faker->uuid())
            );
    }

    public function getSignalement(): Signalement
    {
        $faker = Factory::create('fr_FR');

        return (new Signalement())
            ->setIsProprioAverti(false)
            ->setNbAdultes(2)
            ->setNbEnfantsP6(1)
            ->setNbEnfantsM6(1)
            ->setTelOccupant($faker->phoneNumber())
            ->setAdresseOccupant('25 rue du test')
            ->setEtageOccupant(2)
            ->setVilleOccupant($faker->city())
            ->setCpOccupant(Address::postcode())
            ->setNumAppartOccupant(2)
            ->setNomOccupant($faker->lastName())
            ->setPrenomOccupant($faker->firstName())
            ->addSuivi($this->getSuiviPartner());
    }

    /**
     * @return Signalement[]
     */
    public function getSignalementsWithoutGeolocation($count = 1): array
    {
        $faker = Factory::create('fr_FR');

        $signalements = [];
        for ($i = 0; $i < $count; ++$i) {
            $signalements[] = (new Signalement())
                ->setIsProprioAverti(false)
                ->setNbAdultes(2)
                ->setNbEnfantsP6(1)
                ->setNbEnfantsM6(1)
                ->setTelOccupant($faker->phoneNumber())
                ->setAdresseOccupant('25 rue de l\'est')
                ->setEtageOccupant(2)
                ->setVilleOccupant('Bourg-en-Bresse')
                ->setNumAppartOccupant(2)
                ->setNomOccupant($faker->lastName())
                ->setPrenomOccupant($faker->firstName())
                ->addSuivi($this->getSuiviPartner());
        }

        return $signalements;
    }

    public function getSignalementAffectation(PartnerType $partnerType): Affectation
    {
        $faker = Factory::create('fr_FR');
        $file = __DIR__.'/../../tests/files/sample.png';

        $criticite = (new Criticite())
            ->setCritere(
                (new Critere())
                    ->setLabel('critere')
                    ->setDescription('description critere')
                    ->setSituation(
                        (new Situation())
                            ->setLabel('situation')
                            ->setMenuLabel('menu-situation')
                    )
            )
            ->setLabel('criticite')
            ->setScore(2);

        $signalement = $this->getSignalement();
        $signalement
            ->addCriticite($criticite)
            ->addFile($this->getDocumentFile())
            ->addFile($this->getPhotoFile());

        $partner = (new Partner())
            ->setNom($faker->company())
            ->setEsaboraUrl($faker->url())
            ->setEsaboraToken($faker->password(20))
            ->setType($partnerType);

        return (new Affectation())->setSignalement($signalement)->setPartner($partner);
    }

    public function getSuiviPartner(): Suivi
    {
        return (new Suivi())
            ->setType(Suivi::TYPE_PARTNER)
            ->setDescription('Problèmes de condensation et de moisissures')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setCreatedBy(new User());
    }

    public function getAdditionalInformationArrete(): array
    {
        return [
            'arrete_numero' => '2023/DD13/00664',
            'arrete_type' => 'Arrêté L.511-11 - Suroccupation',
            'arrete_mainlevee_date' => '01/08/2023',
            'arrete_mainlevee_numero' => '2023-DD13-00173',
        ];
    }

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
            ->addPersonne($this->getDossierMessageSISHPersonneOccupant())
            ->addPersonne($this->getDossierMessageSISHPersonneDeclarant())
            ->addPersonne($this->getDossierMessageSISHPersonneProprietaire());
    }

    protected function getDossierMessageSISHPersonneOccupant(): DossierMessageSISHPersonne
    {
        return (new DossierMessageSISHPersonne())
            ->setType(PersonneType::OCCUPANT->value)
            ->setNom('Occupant')
            ->setPrenom('Occupant')
            ->setEmail('occupant@sish.com')
            ->setTelephone('0600000001');
    }

    protected function getDossierMessageSISHPersonneDeclarant(): DossierMessageSISHPersonne
    {
        return (new DossierMessageSISHPersonne())
            ->setType(PersonneType::DECLARANT->value)
            ->setNom('Declarant')
            ->setPrenom('Declarant')
            ->setEmail('declarant@sish.com')
            ->setTelephone('0600000002');
    }

    protected function getDossierMessageSISHPersonneProprietaire(): DossierMessageSISHPersonne
    {
        return (new DossierMessageSISHPersonne())
            ->setType(PersonneType::PROPRIETAIRE->value)
            ->setNom('Proprietaire')
            ->setPrenom('Proprietaire')
            ->setEmail('proprietaire@sish.com')
            ->setTelephone('0600000003');
    }

    protected function getDossierSISHResponse(string $filename): DossierPushSISHResponse
    {
        $filepath = __DIR__.'/../tools/wiremock/src/Resources/Esabora/sish/'.$filename;
        $responseEsabora = json_decode(file_get_contents($filepath), true);

        return new DossierPushSISHResponse($responseEsabora, 200);
    }

    public function getDossierVisiteSISHCollectionResponse(): DossierVisiteSISHCollectionResponse
    {
        $filepath = __DIR__.'/../tools/wiremock/src/Resources/Esabora/sish/ws_visites_dossier_sas.json';

        return new DossierVisiteSISHCollectionResponse(
            json_decode(file_get_contents($filepath), true),
            200
        );
    }

    /** Cette payload contient un dossier avec un type intervention Visite de contrôle au lieu de Visite contrôle */
    public function getDossierVisiteSISHCollectionWithDossierResponse(): DossierVisiteSISHCollectionResponse
    {
        $filepath = __DIR__.'/../tools/wiremock/src/Resources/Esabora/sish/ws_visites_dossier_sas_en_cours.json';

        return new DossierVisiteSISHCollectionResponse(
            json_decode(file_get_contents($filepath), true),
            200
        );
    }

    public function getDossierArreteSISHCollectionResponse(): DossierArreteSISHCollectionResponse
    {
        $filepath = __DIR__.'/../tools/wiremock/src/Resources/Esabora/sish/ws_arretes_dossier_sas.json';

        return new DossierArreteSISHCollectionResponse(
            json_decode(file_get_contents($filepath), true),
            200
        );
    }

    public function getUser(array $roles): User
    {
        return (new User())
            ->setNom('Doe')
            ->setPrenom('John')
            ->setRoles($roles)
            ->setPartner($this->getPartner())
            ->setTerritory($this->getTerritory())
            ->setStatut(User::STATUS_ACTIVE);
    }

    public function getPartner(): Partner
    {
        $faker = Factory::create();

        return (new Partner())
            ->setId(1)
            ->setNom('ARS')
            ->setType(PartnerType::ARS)
            ->setEmail($faker->email())
            ->setTerritory($this->getTerritory());
    }

    public function getTerritory(
        string $name = 'Ain',
        string $zip = '01',
        int $isActive = 1
    ): Territory {
        return (new Territory())
            ->setName($name)
            ->setZip($zip)
            ->setIsActive($isActive);
    }

    public function getClosedTerritory(): Territory
    {
        return (new Territory())
            ->setName('Gard')
            ->setZip('30')
            ->setIsActive(0);
    }

    public function getDocumentFile(): File
    {
        return (new File())
            ->setFilename('document.pdf')
            ->setTitle('Doc')
            ->setFileType(File::FILE_TYPE_DOCUMENT)
            ->setCreatedAt(new \DateTimeImmutable('2022-12-02'));
    }

    public function getPhotoFile(): File
    {
        return (new File())
            ->setFilename('photo.jpg')
            ->setTitle('Photo')
            ->setFileType(File::FILE_TYPE_PHOTO)
            ->setCreatedAt(new \DateTimeImmutable('2022-12-02'));
    }

    public function getIntervention(
        InterventionType $interventionType,
        \DateTimeImmutable $scheduledAt,
        string $status
    ): Intervention {
        return (new Intervention())
            ->setSignalement($this->getSignalement())
            ->setPartner($this->getPartner())
            ->setType($interventionType)
            ->setScheduledAt($scheduledAt)
            ->setStatus($status);
    }

    public function getLocataireTypeComposition(bool $transformPiecesAVivre = false): array
    {
        $typeCompostion = [
            'bail_dpe_dpe' => 'oui',
            'bail_dpe_bail' => 'oui',
            'type_logement_rdc' => 'non',
            'type_logement_nature' => 'appartement',
            'bail_dpe_etat_des_lieux' => 'oui',
            'bail_dpe_date_emmenagement' => '2020-10-01',
            'type_logement_commodites_wc' => 'oui',
            'type_logement_dernier_etage' => 'non',
            'composition_logement_enfants' => 'oui',
            'composition_logement_nb_pieces' => '2',
            'composition_logement_superficie' => '45',
            'composition_logement_hauteur' => 'oui',
            'type_logement_commodites_cuisine' => 'oui',
            'type_logement_commodites_piece_a_vivre_9m' => 'oui',
            'composition_logement_piece_unique' => 'plusieurs_pieces',
            'type_logement_commodites_wc_cuisine' => 'non',
            'type_logement_sous_sol_sans_fenetre' => 'non',
            'composition_logement_nombre_personnes' => '3',
            'type_logement_commodites_salle_de_bain' => 'oui',
            'type_logement_commodites_salle_de_bain_collective' => 'oui',
        ];

        return $typeCompostion;
    }

    public function getLocataireSituationFoyer(): array
    {
        return [
            'logement_social_allocation' => 'oui',
            'logement_social_date_naissance' => '1970-10-01',
            'logement_social_allocation_caisse' => 'caf',
            'travailleur_social_accompagnement' => 'oui',
            'logement_social_demande_relogement' => 'oui',
            'logement_social_montant_allocation' => '300',
            'logement_social_numero_allocataire' => '12345678',
            'travailleur_social_quitte_logement' => 'non',
        ];
    }

    public function getLocataireInformationProcedure(): array
    {
        return [
            'utilisation_service_ok_visite' => 1,
            'info_procedure_bailleur_prevenu' => 'oui',
            'info_procedure_assurance_contactee' => 'oui',
            'info_procedure_depart_apres_travaux' => 'oui',
            'utilisation_service_ok_demande_logement' => 1,
            'utilisation_service_ok_prevenir_bailleur' => 1,
            'info_procedure_reponse_assurance' => 'Dossier reçu',
        ];
    }

    public function getLocataireInformationComplementaire(): array
    {
        return [
            'informations_complementaires_logement_montant_loyer' => '500',
            'informations_complementaires_logement_nombre_etages' => '5',
            'informations_complementaires_logement_annee_construction' => '1970',
            'informations_complementaires_situation_occupants_beneficiaire_fsl' => 'non',
            'informations_complementaires_situation_occupants_beneficiaire_rsa' => 'non',
        ];
    }
}
