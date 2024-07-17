<?php

namespace App\Tests\Unit\Dto\Request\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SignalementDraftRequestTest extends WebTestCase
{
    private ?ValidatorInterface $validator = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = self::getContainer()->get('validator');
    }

    public function testValidateSuccess(): void
    {
        $signalementDraftRequest = new SignalementDraftRequest();
        $signalementDraftRequest
            ->setProfil('locataire')
            ->setCurrentStep('Step 1')
            ->setAdresseLogementAdresse('123 Rue de Exemple')
            ->setAdresseLogementAdresseDetailNumero('1A')
            ->setAdresseLogementAdresseDetailCodePostal('13129')
            ->setAdresseLogementAdresseDetailCommune('Arles')
            ->setAdresseLogementAdresseDetailInsee('13004')
            ->setAdresseLogementAdresseDetailGeolocLat(48.8566)
            ->setAdresseLogementAdresseDetailGeolocLng(2.3522)
            ->setAdresseLogementAdresseDetailManual(true)
            ->setAdresseLogementComplementAdresseEscalier('A')
            ->setAdresseLogementComplementAdresseEtage('3')
            ->setAdresseLogementComplementAdresseNumeroAppartement('42')
            ->setAdresseLogementComplementAdresseAutre('Proche de la boulangerie')
            ->setSignalementConcerneProfil('logement_occupez')
            ->setSignalementConcerneProfilDetailOccupant('locataire')
            ->setSignalementConcerneProfilDetailTiers('tiers_pro')
            ->setSignalementConcerneProfilDetailBailleurProprietaire('particulier')
            ->setSignalementConcerneProfilDetailBailleurBailleur('organisme_societe')
            ->setSignalementConcerneLogementSocialServiceSecours('oui')
            ->setSignalementConcerneLogementSocialAutreTiers('oui')
            ->setVosCoordonneesTiersNomOrganisme('Organisme Exemple')
            ->setVosCoordonneesTiersLien('proche')
            ->setVosCoordonneesTiersNom('Dupont')
            ->setVosCoordonneesTiersPrenom('Jean')
            ->setVosCoordonneesTiersEmail('jean.dupont@example.com')
            ->setVosCoordonneesTiersTel('0123456789')
            ->setVosCoordonneesOccupantCivilite('mr')
            ->setVosCoordonneesOccupantNomOrganisme('Organisme Occupant')
            ->setVosCoordonneesOccupantNom('Dupont')
            ->setVosCoordonneesOccupantPrenom('Jean')
            ->setVosCoordonneesOccupantEmail('jean.dupont@example.com')
            ->setVosCoordonneesOccupantTel('0123456789')
            ->setCoordonneesOccupantNom('Occupant Nom')
            ->setCoordonneesOccupantPrenom('Occupant Prenom')
            ->setCoordonneesOccupantEmail('occupant@example.com')
            ->setCoordonneesOccupantTel('0123456789')
            ->setCoordonneesOccupantTelSecondaire('0123456788')
            ->setCoordonneesBailleurNom('Bailleur Nom')
            ->setCoordonneesBailleurPrenom('Bailleur Prenom')
            ->setCoordonneesBailleurEmail('bailleur@example.com')
            ->setCoordonneesBailleurTel('0123456789')
            ->setCoordonneesBailleurAdresse('123 Rue Bailleur')
            ->setCoordonneesBailleurAdresseDetailNumero('2B')
            ->setCoordonneesBailleurAdresseDetailCodePostal('75002')
            ->setCoordonneesBailleurAdresseDetailCommune('Paris')
            ->setZoneConcerneeZone('batiment')
            ->setTypeLogementNature('appartement')
            ->setTypeLogementNatureAutrePrecision('Précision')
            ->setTypeLogementRdc('oui')
            ->setTypeLogementDernierEtage('non')
            ->setTypeLogementSousSolSansFenetre('non')
            ->setTypeLogementSousCombleSansFenetre('oui')
            ->setCompositionLogementPieceUnique('piece_unique')
            ->setCompositionLogementSuperficie('50')
            ->setCompositionLogementHauteur('oui')
            ->setCompositionLogementNbPieces('2')
            ->setCompositionLogementNombrePersonnes('3')
            ->setCompositionLogementEnfants('oui')
            ->setTypeLogementCommoditesPieceAVivre9m('oui')
            ->setTypeLogementCommoditesCuisine('oui')
            ->setTypeLogementCommoditesCuisineCollective('non')
            ->setTypeLogementCommoditesSalleDeBain('oui')
            ->setTypeLogementCommoditesSalleDeBainCollective('non')
            ->setTypeLogementCommoditesWc('oui')
            ->setTypeLogementCommoditesWcCollective('non')
            ->setTypeLogementCommoditesWcCuisine('non')
            ->setBailDpeDateEmmenagement('2022-01-01')
            ->setBailDpeBail('oui')
            ->setBailDpeEtatDesLieux('oui')
            ->setBailDpeDpe('oui')
            ->setLogementSocialDemandeRelogement('oui')
            ->setLogementSocialAllocation('oui')
            ->setLogementSocialAllocationCaisse('caf')
            ->setLogementSocialDateNaissance('1990-01-01')
            ->setLogementSocialMontantAllocation('500')
            ->setLogementSocialNumeroAllocataire('123456')
            ->setTravailleurSocialQuitteLogement('oui')
            ->setTravailleurSocialPreavisDepart('oui')
            ->setTravailleurSocialAccompagnement('oui')
            ->setTravailleurSocialAccompagnementDeclarant('1')
            ->setInfoProcedureBailleurPrevenu('oui')
            ->setInfoProcedureAssuranceContactee('oui')
            ->setInfoProcedureReponseAssurance('Réponse de l\'assurance')
            ->setInfoProcedureDepartApresTravaux('oui')
            ->setUtilisationServiceOkPrevenirBailleur(true)
            ->setUtilisationServiceOkVisite(true)
            ->setUtilisationServiceOkDemandeLogement(true)
            ->setInformationsComplementairesSituationOccupantsBeneficiaireRsa('oui')
            ->setInformationsComplementairesSituationOccupantsBeneficiaireFsl('non')
            ->setInformationsComplementairesSituationOccupantsDateNaissance('1990-01-01')
            ->setInformationsComplementairesSituationOccupantsDemandeRelogement('oui')
            ->setInformationsComplementairesSituationOccupantsDateEmmenagement('2022-01-01')
            ->setInformationsComplementairesSituationOccupantsLoyersPayes('oui')
            ->setInformationsComplementairesSituationBailleurBeneficiaireRsa('non')
            ->setInformationsComplementairesSituationBailleurBeneficiaireFsl('oui')
            ->setInformationsComplementairesSituationBailleurRevenuFiscal('30000')
            ->setInformationsComplementairesSituationBailleurDateNaissance('1980-01-01')
            ->setInformationsComplementairesLogementMontantLoyer('750')
            ->setInformationsComplementairesLogementNombreEtages('3')
            ->setInformationsComplementairesLogementAnneeConstruction('2000')
            ->setMessageAdministration('Message administration');

        $this->assertSame('logement_occupez', $signalementDraftRequest->getSignalementConcerneProfil());
        $this->assertSame('locataire', $signalementDraftRequest->getSignalementConcerneProfilDetailOccupant());
        $this->assertSame('tiers_pro', $signalementDraftRequest->getSignalementConcerneProfilDetailTiers());
        $this->assertSame('123 Rue Bailleur', $signalementDraftRequest->getCoordonneesBailleurAdresse());
        $this->assertSame('proche', $signalementDraftRequest->getVosCoordonneesTiersLien());
        $this->assertSame('batiment', $signalementDraftRequest->getZoneConcerneeZone());
        $this->assertSame('Précision', $signalementDraftRequest->getTypeLogementNatureAutrePrecision());
        $this->assertSame('oui', $signalementDraftRequest->getTypeLogementRdc());
        $this->assertSame('non', $signalementDraftRequest->getTypeLogementDernierEtage());
        $this->assertSame('non', $signalementDraftRequest->getTypeLogementSousSolSansFenetre());
        $this->assertSame('oui', $signalementDraftRequest->getTypeLogementSousCombleSansFenetre());
        $this->assertSame('piece_unique', $signalementDraftRequest->getCompositionLogementPieceUnique());
        $this->assertSame('oui', $signalementDraftRequest->getCompositionLogementHauteur());
        $this->assertSame('oui', $signalementDraftRequest->getCompositionLogementEnfants());
        $this->assertSame('oui', $signalementDraftRequest->getTypeLogementCommoditesPieceAVivre9m());
        $this->assertSame('oui', $signalementDraftRequest->getTypeLogementCommoditesCuisine());
        $this->assertSame('non', $signalementDraftRequest->getTypeLogementCommoditesCuisineCollective());
        $this->assertSame('oui', $signalementDraftRequest->getTypeLogementCommoditesSalleDeBain());
        $this->assertSame('non', $signalementDraftRequest->getTypeLogementCommoditesSalleDeBainCollective());
        $this->assertSame('oui', $signalementDraftRequest->getTypeLogementCommoditesWc());
        $this->assertSame('non', $signalementDraftRequest->getTypeLogementCommoditesWcCollective());
        $this->assertSame('non', $signalementDraftRequest->getTypeLogementCommoditesWcCuisine());
        $this->assertSame('oui', $signalementDraftRequest->getBailDpeEtatDesLieux());
        $this->assertSame('oui', $signalementDraftRequest->getBailDpeDpe());
        $this->assertSame('oui', $signalementDraftRequest->getTravailleurSocialAccompagnement());
        $this->assertSame('1', $signalementDraftRequest->getTravailleurSocialAccompagnementDeclarant());
        $this->assertSame('oui', $signalementDraftRequest->getInfoProcedureAssuranceContactee());
        $this->assertSame(
            'Réponse de l\'assurance',
            $signalementDraftRequest->getInfoProcedureReponseAssurance()
        );
        $this->assertSame('oui', $signalementDraftRequest->getInfoProcedureDepartApresTravaux());
        $this->assertTrue($signalementDraftRequest->getUtilisationServiceOkPrevenirBailleur());
        $this->assertTrue($signalementDraftRequest->getUtilisationServiceOkVisite());
        $this->assertTrue($signalementDraftRequest->getUtilisationServiceOkDemandeLogement());
        $this->assertSame(
            '1990-01-01',
            $signalementDraftRequest->getInformationsComplementairesSituationOccupantsDateNaissance()
        );
        $this->assertSame(
            'oui',
            $signalementDraftRequest->getInformationsComplementairesSituationOccupantsLoyersPayes()
        );
        $this->assertSame(
            'non',
            $signalementDraftRequest->getInformationsComplementairesSituationBailleurBeneficiaireRsa()
        );
        $this->assertSame(
            'oui',
            $signalementDraftRequest->getInformationsComplementairesSituationBailleurBeneficiaireFsl()
        );
        $this->assertSame(
            '30000',
            $signalementDraftRequest->getInformationsComplementairesSituationBailleurRevenuFiscal()
        );
        $this->assertSame(
            '1980-01-01',
            $signalementDraftRequest->getInformationsComplementairesSituationBailleurDateNaissance()
        );

        $errors = $this->validator->validate($signalementDraftRequest);
        $this->assertCount(0, $errors);
    }

    public function testValidateFailed(): void
    {
        $signalementDraftRequest = new SignalementDraftRequest();
        $signalementDraftRequest->setProfil('invalid_profil')
            ->setCurrentStep(str_repeat('a', 129))
            ->setAdresseLogementAdresse('')
            ->setAdresseLogementAdresseDetailNumero(str_repeat('b', 101))
            ->setAdresseLogementAdresseDetailCodePostal('1234')
            ->setAdresseLogementAdresseDetailCommune('')
            ->setAdresseLogementAdresseDetailInsee('1234')
            ->setAdresseLogementAdresseDetailGeolocLat(999.999)
            ->setAdresseLogementAdresseDetailGeolocLng(999.999)
            ->setAdresseLogementComplementAdresseEscalier(str_repeat('c', 4))
            ->setAdresseLogementComplementAdresseEtage(str_repeat('d', 6))
            ->setAdresseLogementComplementAdresseNumeroAppartement(str_repeat('e', 6))
            ->setAdresseLogementComplementAdresseAutre(str_repeat('f', 256))
            ->setSignalementConcerneProfil('invalid_profile_detail')
            ->setSignalementConcerneProfilDetailOccupant('invalid_occupant_detail')
            ->setSignalementConcerneProfilDetailTiers('invalid_tiers_detail')
            ->setSignalementConcerneProfilDetailBailleurProprietaire('invalid_proprietaire_detail')
            ->setSignalementConcerneProfilDetailBailleurBailleur('invalid_bailleur_detail')
            ->setSignalementConcerneLogementSocialServiceSecours('invalid_service_secours')
            ->setSignalementConcerneLogementSocialAutreTiers('invalid_autre_tiers')
            ->setVosCoordonneesTiersNomOrganisme(str_repeat('g', 201))
            ->setVosCoordonneesTiersNom(str_repeat('h', 51))
            ->setVosCoordonneesTiersPrenom(str_repeat('i', 51))
            ->setVosCoordonneesTiersEmail('invalid-email')
            ->setVosCoordonneesTiersTel('invalid-phone')
            ->setVosCoordonneesOccupantCivilite('invalid_civilite')
            ->setVosCoordonneesOccupantNomOrganisme(str_repeat('j', 201))
            ->setVosCoordonneesOccupantNom(str_repeat('k', 51))
            ->setVosCoordonneesOccupantPrenom(str_repeat('l', 51))
            ->setVosCoordonneesOccupantEmail('invalid-email')
            ->setVosCoordonneesOccupantTel('invalid-phone')
            ->setCoordonneesOccupantNom(str_repeat('m', 51))
            ->setCoordonneesOccupantPrenom(str_repeat('n', 51))
            ->setCoordonneesOccupantEmail('invalid-email')
            ->setCoordonneesOccupantTel('invalid-phone')
            ->setCoordonneesBailleurNom(str_repeat('o', 251))
            ->setCoordonneesBailleurPrenom(str_repeat('p', 251))
            ->setCoordonneesBailleurEmail('invalid-email')
            ->setCoordonneesBailleurTel('invalid-phone')
            ->setCoordonneesBailleurAdresse(str_repeat('q', 256))
            ->setCoordonneesBailleurAdresseDetailNumero(str_repeat('r', 256))
            ->setCoordonneesBailleurAdresseDetailCodePostal('1234')
            ->setCoordonneesBailleurAdresseDetailCommune(str_repeat('s', 256))
            ->setZoneConcerneeZone('invalid_zone')
            ->setTypeLogementNature('invalid_nature')
            ->setTypeLogementNatureAutrePrecision(str_repeat('t', 26))
            ->setTypeLogementRdc('invalid_rdc')
            ->setTypeLogementDernierEtage('invalid_dernier_etage')
            ->setTypeLogementSousSolSansFenetre('invalid_sous_sol')
            ->setTypeLogementSousCombleSansFenetre('invalid_sous_comble')
            ->setCompositionLogementPieceUnique('invalid_piece_unique')
            ->setCompositionLogementSuperficie('invalid_superficie')
            ->setCompositionLogementHauteur('invalid_hauteur')
            ->setCompositionLogementNbPieces('invalid_nb_pieces')
            ->setCompositionLogementNombrePersonnes('invalid_nombre_personnes')
            ->setCompositionLogementEnfants('invalid_enfants')
            ->setTypeLogementCommoditesPieceAVivre9m('invalid_9m')
            ->setTypeLogementCommoditesCuisine('invalid_cuisine')
            ->setTypeLogementCommoditesCuisineCollective('invalid_cuisine_collective')
            ->setTypeLogementCommoditesSalleDeBain('invalid_salle_de_bain')
            ->setTypeLogementCommoditesSalleDeBainCollective('invalid_salle_de_bain_collective')
            ->setTypeLogementCommoditesWc('invalid_wc')
            ->setTypeLogementCommoditesWcCollective('invalid_wc_collective')
            ->setTypeLogementCommoditesWcCuisine('invalid_wc_cuisine')
            ->setBailDpeDateEmmenagement('invalid_date')
            ->setBailDpeBail('invalid_bail')
            ->setBailDpeEtatDesLieux('invalid_etat_des_lieux')
            ->setBailDpeDpe('invalid_dpe')
            ->setLogementSocialDemandeRelogement('invalid_demande_relogement')
            ->setLogementSocialAllocation('invalid_allocation')
            ->setLogementSocialAllocationCaisse('invalid_caisse')
            ->setLogementSocialDateNaissance('invalid_date')
            ->setLogementSocialMontantAllocation('invalid_montant')
            ->setLogementSocialNumeroAllocataire(str_repeat('u', 51))
            ->setTravailleurSocialQuitteLogement('invalid_quitte_logement')
            ->setTravailleurSocialPreavisDepart('invalid_preavis_depart')
            ->setTravailleurSocialAccompagnement('invalid_accompagnement')
            ->setInfoProcedureBailleurPrevenu('invalid_bailleur_prevenu')
            ->setInfoProcedureAssuranceContactee('invalid_assurance_contactee')
            ->setInfoProcedureReponseAssurance(str_repeat('v', 256))
            ->setInfoProcedureDepartApresTravaux('invalid_depart_apres_travaux')
            ->setUtilisationServiceOkPrevenirBailleur(null)
            ->setUtilisationServiceOkVisite(null)
            ->setUtilisationServiceOkDemandeLogement(null)
            ->setInformationsComplementairesSituationOccupantsBeneficiaireRsa('invalid_beneficiaire_rsa')
            ->setInformationsComplementairesSituationOccupantsBeneficiaireFsl('invalid_beneficiaire_fsl')
            ->setInformationsComplementairesSituationOccupantsDateNaissance('invalid_date')
            ->setInformationsComplementairesSituationOccupantsDemandeRelogement('invalid_demande_relogement')
            ->setInformationsComplementairesSituationOccupantsDateEmmenagement('invalid_date')
            ->setInformationsComplementairesSituationOccupantsLoyersPayes('invalid_loyers_payes')
            ->setInformationsComplementairesSituationBailleurBeneficiaireRsa('invalid_beneficiaire_rsa')
            ->setInformationsComplementairesSituationBailleurBeneficiaireFsl('invalid_beneficiaire_fsl')
            ->setInformationsComplementairesSituationBailleurRevenuFiscal(str_repeat('w', 51))
            ->setInformationsComplementairesSituationBailleurDateNaissance('invalid_date')
            ->setInformationsComplementairesLogementMontantLoyer(str_repeat('x', 21))
            ->setInformationsComplementairesLogementNombreEtages(str_repeat('y', 6))
            ->setInformationsComplementairesLogementAnneeConstruction('invalid_annee')
            ->setMessageAdministration('Message administration');

        $errors = $this->validator->validate($signalementDraftRequest);
        $this->assertCount(97, $errors);
    }
}
