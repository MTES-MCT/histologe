<?php

namespace App\Tests;

use App\Entity\Affectation;
use App\Entity\AutoAffectationRule;
use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\UserStatus;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Situation;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Messenger\Message\Esabora\DossierMessageSCHS;
use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\Enum\PersonneType;
use App\Service\Interconnection\Esabora\Model\DossierMessageSISHPersonne;
use App\Service\Interconnection\Esabora\Response\DossierArreteSISHCollectionResponse;
use App\Service\Interconnection\Esabora\Response\DossierPushSISHResponse;
use App\Service\Interconnection\Esabora\Response\DossierVisiteSISHCollectionResponse;
use App\Utils\Enum\ExtensionAdresse;
use Faker\Factory;

trait FixturesHelper
{
    public function getAffectation(PartnerType $partnerType, bool $isSynchronized = false): Affectation
    {
        $faker = Factory::create();

        return (new Affectation())
            ->setIsSynchronized($isSynchronized)
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

    public function getSignalement(
        ?Territory $territory = null,
        ?ProfileDeclarant $profileDeclarant = null,
        ?string $nom = null,
        ?string $prenom = null,
        ?string $codePostal = null,
        ?string $codeSuivi = null,
        ?string $email = null,
    ): Signalement {
        $faker = Factory::create('fr_FR');

        $signalement = (new Signalement())
           ->setIsProprioAverti(false)
           ->setNbAdultes((string) 2)
           ->setNbEnfantsP6((string) 1)
           ->setNbEnfantsM6((string) 1)
           ->setTelOccupant($faker->phoneNumber())
           ->setAdresseOccupant('25 rue du test')
           ->setEtageOccupant('2')
           ->setVilleOccupant('Calais')
           ->setCpOccupant($codePostal ?? '62100')
           ->setNumAppartOccupant('2')
           ->setCiviliteOccupant('mme')
           ->setNomOccupant($nom ?? $faker->lastName())
           ->setPrenomOccupant($prenom ?? $faker->firstName())
           ->setTelOccupant($faker->phoneNumber())
           ->setMailOccupant($email ?? $faker->email())
           ->setNomProprio($faker->lastName())
           ->setPrenomProprio($faker->firstName())
           ->setAdresseProprio('27 rue de la république')
           ->setCodePostalProprio('13002')
           ->setVilleProprio('Marseille')
           ->setTerritory($territory)
           ->setInseeOccupant('62193')
           ->setProfileDeclarant($profileDeclarant ?? ProfileDeclarant::LOCATAIRE)
           ->setValidatedAt(new \DateTimeImmutable())
           ->setScore(1.46265448)
           ->setSuperficie(75.5)
           ->addSuivi($this->getSuiviPartner());

        if (null !== $profileDeclarant && ProfileDeclarant::LOCATAIRE !== $profileDeclarant) {
            $signalement
                ->setProfileDeclarant($profileDeclarant)
                ->setPrenomDeclarant($prenom)
                ->setNomDeclarant($nom)
                ->setMailDeclarant($email)
                ->setCpOccupant($codePostal);
        }

        if ($codeSuivi) {
            $signalement->setCodeSuivi($codeSuivi);
        }

        return $signalement;
    }

    public function getSignalementLocataire(): Signalement
    {
        return $this->getSignalement(
            profileDeclarant: ProfileDeclarant::LOCATAIRE,
            nom: 'Martin',
            prenom: 'Luc',
            codePostal: '13001',
            codeSuivi: '12345678',
            email: 'luc.martin@example.com'
        );
    }

    /**
     * @return Signalement[]
     */
    public function getSignalements(int $count = 1): array
    {
        $faker = Factory::create('fr_FR');

        $signalements = [];
        for ($i = 0; $i < $count; ++$i) {
            $signalements[] = (new Signalement())
                ->setIsProprioAverti(false)
                ->setNbAdultes((string) 2)
                ->setNbEnfantsP6((string) 1)
                ->setNbEnfantsM6((string) 1)
                ->setTelOccupant($faker->phoneNumber())
                ->setAdresseOccupant('25 rue de l\'est')
                ->setEtageOccupant('2')
                ->setVilleOccupant('Bourg-en-Bresse')
                ->setNumAppartOccupant('2')
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
            ->setNom('ARS')
            ->setEsaboraUrl($faker->url())
            ->setEsaboraToken($faker->password(20))
            ->setType($partnerType);

        return (new Affectation())->setSignalement($signalement)->setPartner($partner);
    }

    public function getSuiviPartner(
        string $description = 'Problèmes de condensation et de moisissures',
        ?Partner $partner = null,
    ): Suivi {
        return (new Suivi())
            ->setType(Suivi::TYPE_PARTNER)
            ->setDescription($description)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setCreatedBy($this->getUser([User::ROLE_USER_PARTNER]))
            ->setPartner($partner);
    }

    /**
     * @return array<Suivi>
     */
    public function getSuiviPartnerList(Partner $partner): array
    {
        return [
            $this
                ->getSuiviPartner('Problèmes de condensation et de moisissure', $partner)
                ->setSignalement($this->getSignalement($this->getTerritory())),
            $this
                ->getSuiviPartner('Problèmes d\'humidité dans le logement', $partner)
                ->setSignalement($this->getSignalement($this->getTerritory())),
            $this
                ->getSuiviPartner('Absence de chauffage', $partner)
                ->setSignalement($this->getSignalement($this->getTerritory())),
            $this
                ->getSuiviPartner('Ventilation défectueuse', $partner)
                ->setSignalement($this->getSignalement($this->getTerritory())),
        ];
    }

    /**
     * @return array<string>
     */
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
            ->setAction('push_dossier')
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

    protected function getDossierMessageSISH(?string $action = null): DossierMessageSISH
    {
        $faker = Factory::create('fr_FR');
        $uuid = $faker->uuid();

        $dossierMessageSISH = (new DossierMessageSISH())
            ->setUrl($faker->url())
            ->setToken($faker->password(20))
            ->setPartnerId($faker->randomDigit())
            ->setPartnerType(PartnerType::ARS)
            ->setSignalementId($faker->randomDigit())
            ->setReferenceAdresse($uuid)
            ->setLocalisationNumero((string) $faker->randomDigit())
            ->setLocalisationNumeroExt(ExtensionAdresse::BIS->name)
            ->setLocalisationAdresse1($faker->streetName())
            ->setLocalisationAdresse2(null)
            ->setLocalisationCodePostal($faker->postcode())
            ->setLocalisationVille($faker->city())
            ->setLocalisationLocalisationInsee($faker->postcode())
            ->setSasLogicielProvenance('H')
            ->setReferenceDossier($uuid)
            ->setSasDateAffectation('25/04/2023 15:01')
            ->setLocalisationEtage((string) $faker->randomDigit())
            ->setLocalisationEscalier((string) $faker->randomDigit())
            ->setLocalisationNumPorte((string) $faker->randomDigit())
            ->setSitOccupantNbAdultes('3')
            ->setSitOccupantNbEnfantsM6('4')
            ->setSitOccupantNbEnfantsP6('2')
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

        if ($action) {
            $dossierMessageSISH->setAction($action);
        }

        return $dossierMessageSISH;
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

    /**
     * @param array<mixed> $roles
     */
    public function getUser(array $roles): User
    {
        $user = (new User())
            ->setNom('Doe')
            ->setPrenom('John')
            ->setRoles($roles)
            ->setStatut(UserStatus::ACTIVE);
        $userPartner = (new UserPartner())->setPartner($this->getPartner())->setUser($user);
        $user->addUserPartner($userPartner);

        return $user;
    }

    public function getPartner(bool $isOperatorExterne = false): Partner
    {
        $faker = Factory::create();

        $partner = (new Partner())
            ->setId(1)
            ->setNom('ARS')
            ->setType(PartnerType::ARS)
            ->setEmail($faker->email());

        if (!$isOperatorExterne) {
            $partner->setTerritory($this->getTerritory());
        }

        return $partner;
    }

    public function getTerritory(
        string $name = 'Ain',
        string $zip = '01',
        int $isActive = 1,
    ): Territory {
        return (new Territory())
            ->setName($name)
            ->setZip($zip)
            ->setIsActive((bool) $isActive);
    }

    public function getClosedTerritory(): Territory
    {
        return (new Territory())
            ->setName('Gard')
            ->setZip('30')
            ->setIsActive((bool) 0);
    }

    public function getDocumentFile(): File
    {
        return (new File())
            ->setFilename('document.pdf')
            ->setTitle('Doc')
            ->setExtension('pdf')
            ->setCreatedAt(new \DateTimeImmutable('2022-12-02'));
    }

    public function getPhotoFile(): File
    {
        return (new File())
            ->setFilename('photo.jpg')
            ->setTitle('Photo')
            ->setExtension('jpg')
            ->setCreatedAt(new \DateTimeImmutable('2022-12-02'));
    }

    public function getIntervention(
        InterventionType $interventionType,
        \DateTimeImmutable $scheduledAt,
        string $status,
    ): Intervention {
        return (new Intervention())
            ->setSignalement($this->getSignalement())
            ->setPartner($this->getPartner())
            ->setType($interventionType)
            ->setScheduledAt($scheduledAt)
            ->setStatus($status);
    }

    /**
     * @return array<string>
     */
    public function getLocataireTypeComposition(bool $transformPiecesAVivre = false): array
    {
        $typeComposition = [
            'bail_dpe_dpe' => 'oui',
            'bail_dpe_classe_energetique' => 'G',
            'bail_dpe_bail' => 'oui',
            'type_logement_rdc' => 'non',
            'type_logement_nature' => 'appartement',
            'bail_dpe_etat_des_lieux' => 'oui',
            'bail_dpe_date_emmenagement' => '2020-10-01',
            'type_logement_commodites_wc' => 'oui',
            'type_logement_dernier_etage' => 'non',
            'composition_logement_enfants' => 'oui',
            'composition_logement_nombre_enfants' => '1',
            'composition_logement_nb_pieces' => '2',
            'composition_logement_superficie' => '45',
            'type_logement_commodites_cuisine' => 'oui',
            'type_logement_commodites_piece_a_vivre_9m' => 'oui',
            'composition_logement_piece_unique' => 'plusieurs_pieces',
            'type_logement_commodites_wc_cuisine' => 'non',
            'type_logement_sous_sol_sans_fenetre' => 'non',
            'composition_logement_nombre_personnes' => '3',
            'type_logement_commodites_salle_de_bain' => 'oui',
            'type_logement_commodites_salle_de_bain_collective' => 'oui',
            'desordres_logement_chauffage_details_dpe_annee' => 'post2023',
        ];

        return $typeComposition;
    }

    /**
     * @return array<string>
     */
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

    /**
     * @return array<mixed>
     */
    public function getLocataireInformationProcedure(): array
    {
        return [
            'utilisation_service_ok_visite' => 1,
            'info_procedure_bailleur_prevenu' => 'oui',
            'info_procedure_bail_moyen' => 'courrier',
            'info_procedure_bail_date' => '11/2024',
            'info_procedure_bail_reponse' => 'Réponse du bailleur',
            'info_procedure_bail_numero' => 'R-TR45',
            'info_procedure_assurance_contactee' => 'oui',
            'info_procedure_depart_apres_travaux' => 'oui',
            'utilisation_service_ok_demande_logement' => 1,
            'utilisation_service_ok_prevenir_bailleur' => 1,
            'utilisation_service_ok_cgu' => 1,
            'info_procedure_reponse_assurance' => 'Dossier reçu',
        ];
    }

    /**
     * @return array<string>
     */
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

    public function getAutoAffectationRule(
    ): AutoAffectationRule {
        return (new AutoAffectationRule())
            ->setTerritory($this->getTerritory())
            ->setPartnerType(PartnerType::CAF_MSA)
            ->setProfileDeclarant('all')
            ->setParc('prive')
            ->setAllocataire('oui')
            ->setInseeToInclude('')
            ->setInseeToExclude(null)
            ->setPartnerToExclude([])
            ->setStatus(AutoAffectationRule::STATUS_ACTIVE);
    }
}
