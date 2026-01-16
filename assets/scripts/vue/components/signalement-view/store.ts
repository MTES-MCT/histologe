import { QueryParameter } from './interfaces/queryParameter'
import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'
import SearchInterfaceSelectOption from './interfaces/SearchInterfaceSelectOption'

export const store = {
  state: {
    signalements: {
      filters: Object,
      list: new Array<Object>(),
      pagination: {
        current_page: 1,
        total_pages: 1,
        total_items: 0,
        per_page: 10,
      },
      zoneAreas: new Array<string>(),
    },
    input: {
      order: 'reference-DESC',
      queryParameters: [] as QueryParameter[],
      filters: {
        territoire: undefined,
        etiquettes: new Array<string>(),
        zones: new Array<string>(),
        partenaires: new Array<string>(),
        communes: new Array<string>(),
        epcis: new Array<string>(),
        searchTerms: undefined,
        status: undefined,
        procedure: undefined,
        procedureConstatee: undefined,
        visiteStatus: undefined,
        typeDernierSuivi: undefined,
        typeDeclarant: undefined,
        natureParc: undefined,
        bailleurSocial: undefined,
        allocataire: undefined,
        enfantsM6: undefined,
        situation: undefined,
        dateDepot: undefined,
        dateDernierSuivi: undefined,
        isImported: undefined as 'oui' | undefined,
        isZonesDisplayed: undefined as 'oui' | undefined,
        showMyAffectationOnly: undefined as 'oui' | undefined,
        showMySignalementsOnly: undefined as 'oui' | undefined,
        isMessagePostCloture: undefined as 'oui' | undefined,
        isNouveauMessage: undefined as 'oui' | undefined,
        isMessageWithoutResponse: undefined as 'oui' | undefined,
        isDossiersSansActivite: undefined as 'oui' | undefined,
        isEmailAVerifier: undefined as 'oui' | undefined,
        isDossiersSansAgent: undefined as 'oui' | undefined,
        isActiviteRecente: undefined as 'oui' | undefined,
        showWithoutAffectationOnly: undefined as 'oui' | undefined,
        statusAffectation: undefined,
        criticiteScoreMin: undefined,
        criticiteScoreMax: undefined,
        motifCloture: undefined,
        createdFrom: undefined,
        relanceUsagerSansReponse: undefined as 'oui' | undefined
      }
    },
    user: {
      isAdmin: false,
      isResponsableTerritoire: false,
      isAdministrateurPartenaire: false,
      isAgent: false,
      isMultiTerritoire: false,
      canSeeStatusAffectation: false,
      canSeeScore: false,
      canSeeWithoutAffectation: false,
      partnerIds: new Array<string>()
    },
    showOptions: false,
    territories: new Array<HistoInterfaceSelectOption>(),
    etiquettes: new Array<HistoInterfaceSelectOption>(),
    zones: new Array<HistoInterfaceSelectOption>(),
    partenaires: new Array<HistoInterfaceSelectOption>(),
    bailleursSociaux: new Array<HistoInterfaceSelectOption>(),
    communes: new Array<string>(),
    epcis: new Array<string>(),
    savedSearches: new Array<SearchInterfaceSelectOption>(),
    currentTerritoryId: '',
    currentCommunes: '',
    currentPartenaires: '',
    hasSignalementImported: false,
    loadingList: true,
    hasErrorLoading: false,
    statusSignalementList: [
      { Id: 'nouveau', Text: 'Nouveau' },
      { Id: 'en_cours', Text: 'En cours' },
      { Id: 'ferme', Text: 'Fermé' },
      { Id: 'refuse', Text: 'Refusé' }
    ],
    statusAffectationList: [
      { Id: 'accepte', Text: 'Acceptée' },
      { Id: 'en_attente', Text: 'En attente' },
      { Id: 'refuse', Text: 'Refusée' },
      { Id: 'cloture_un_partenaire', Text: 'Clôturée par au moins un partenaire' },
      { Id: 'cloture_tous_partenaire', Text: 'Clôturée par tous les partenaires' }
    ],
    statusVisiteList: [
      { Id: 'Non planifiée', Text: 'Visite non planifiée' },
      { Id: 'Planifiée', Text: 'Visite planifiée' },
      { Id: 'Conclusion à renseigner', Text: 'Conclusion de visite à renseigner' },
      { Id: 'Terminée', Text: 'Visite terminée' }
    ],
    situationList: [
      { Id: 'attente_relogement', Text: 'Attente de relogement' },
      { Id: 'bail_en_cours', Text: 'Bail en cours' },
      { Id: 'preavis_de_depart', Text: 'Préavis de départ' },
      { Id: 'logement_vacant', Text: 'Logement vacant' }
    ],
    procedureList: [
      { Id: 'non_decence_energetique', Text: 'Non décence énergétique' },
      { Id: 'non_decence', Text: 'Non décence' },
      { Id: 'rsd', Text: 'RSD' },
      { Id: 'danger', Text: 'Danger occupant' },
      { Id: 'insalubrite', Text: 'Insalubrité' },
      { Id: 'mise_en_securite_peril', Text: 'Péril' },
      { Id: 'suroccupation', Text: 'Suroccupation' },
      { Id: 'assurantiel', Text: 'Assurantiel' }
    ],
    procedureConstateeList: [
      { Id: 'non_decence', Text: 'Non décence' },
      { Id: 'rsd', Text: 'Infraction RSD' },
      { Id: 'insalubrite', Text: 'Insalubrité' },
      { Id: 'mise_en_securite_peril', Text: 'Mise en sécurité / Péril' },
      { Id: 'logement_decent', Text: 'Logement décent / Pas d\'infraction' },
      { Id: 'responsabilite_occupant_assurantiel', Text: 'Responsabilité occupant / Assurantiel' },
      { Id: 'autre', Text: 'Autre' },
    ],    
    typeDernierSuiviList: [
      { Id: 'partenaire', Text: 'Suivi Partenaire' },
      { Id: 'usager', Text: 'Suivi Usager' },
      { Id: 'automatique', Text: 'Suivi Automatique' }
    ],
    typeDeclarantList: [
      { Id: 'locataire', Text: 'Occupant' },
      { Id: 'bailleur_occupant', Text: 'Bailleur occupant' },
      { Id: 'bailleur', Text: 'Tiers bailleur' },
      { Id: 'tiers_particulier', Text: 'Tiers particulier' },
      { Id: 'tiers_pro', Text: 'Tiers professionnel' },
      { Id: 'service_secours', Text: 'Service de secours' }
    ],
    natureParcList: [
      { Id: 'privee', Text: 'Parc privé' },
      { Id: 'public', Text: 'Parc public' },
      { Id: 'non_renseigne', Text: 'Parc Non renseigné' }
    ],
    allocataireList: [
      { Id: 'non', Text: 'Non allocataire' },
      { Id: 'oui', Text: 'Allocataire' },
      { Id: 'caf', Text: 'Allocataire CAF' },
      { Id: 'msa', Text: 'Allocataire MSA' },
      { Id: 'non_renseigne', Text: 'Allocataire non renseigné' }
    ],
    enfantMoinsSixList: [
      { Id: 'oui', Text: 'Enfant(s) moins de 6ans' },
      { Id: 'non', Text: 'Aucun enfant(s) moins de 6ans' },
      { Id: 'non_renseigne', Text: 'Présence d\'enfants moins de 6ans non renseignée' }
    ],
    motifClotureList: [
      { Id: 'abandon_de_procedure_absence_de_reponse', Text: 'Abandon de procédure / absence de réponse' },
      { Id: 'depart_occupant', Text: 'Départ occupant' },
      { Id: 'insalubrite', Text: 'Insalubrité' },
      { Id: 'logement_decent', Text: 'Logement décent / Pas d\'infraction' },
      { Id: 'logement_vendu', Text: 'Logement vendu' },
      { Id: 'non_decence', Text: 'Non décence' },
      { Id: 'peril', Text: 'Mise en sécurité / Péril' },
      { Id: 'refus_de_visite', Text: 'Refus de visite' },
      { Id: 'refus_de_travaux', Text: 'Refus de travaux' },
      { Id: 'relogement_occupant', Text: 'Relogement occupant' },
      { Id: 'responsabilite_de_l_occupant', Text: 'Responsabilité de l\'occupant / assurantiel' },
      { Id: 'rsd', Text: 'RSD' },
      { Id: 'travaux_faits_ou_en_cours', Text: 'Travaux faits ou en cours' },
      { Id: 'doublon', Text: 'Doublon' },
      { Id: 'autre', Text: 'Autre' }
    ],
    createdFromList: [
      { Id: 'formulaire-usager', Text: 'Formulaire usager (v1, actuel et import)' },
      { Id: 'formulaire-pro', Text: 'Formulaire pro (BO et api)' },
      { Id: 'form-usager-v1', Text: 'Formulaire usager v1' },
      { Id: 'form-usager-v2', Text: 'Formulaire usager actuel' },
      { Id: 'form-pro-bo', Text: 'Formulaire pro BO' },
      { Id: 'api', Text: 'API' },
      { Id: 'import', Text: 'Import' }
    ],
    selectedSavedSearchId: undefined as string | undefined,
    savedSearchSelectKey: 0,
    filtersApplyKey: 0
  },
  props: {
    ajaxurlSignalement: '',
    baseAjaxUrlSignalement: '',
    ajaxurlRemoveSignalement: '',
    ajaxurlExportCsv: '',
    ajaxurlSettings: '',
    ajaxurlContact: '',
    ajaxurlSaveSearch: '',
    csrfSaveSearch: '',
    ajaxurlDeleteSearch: '',
    csrfDeleteSearch: '',
    ajaxurlEditSearch: '',
    csrfEditSearch: '',
    platformName: '',
    token: ''
  }
}

export const PATTERN_BADGE_EPCI = /\d{9}$/
