import { QueryParameter } from './interfaces/queryParameter'
import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'

export const store = {
  state: {
    signalements: {
      filters: Object,
      list: new Array<Object>(),
      pagination: Object,
      zoneAreas: new Array<string>(),
    },
    input: {
      order: 'reference-DESC',
      queryParameters: [] as QueryParameter[],
      filters: {
        territoire: null,
        etiquettes: new Array<HistoInterfaceSelectOption>(),
        zones: new Array<HistoInterfaceSelectOption>(),
        partenaires: new Array<HistoInterfaceSelectOption | string>(),
        communes: new Array<string>(),
        epcis: new Array<string>(),
        searchTerms: null,
        status: null,
        procedure: null,
        procedureConstatee: null,
        visiteStatus: null,
        typeDernierSuivi: null,
        typeDeclarant: null,
        natureParc: null,
        bailleurSocial: null,
        allocataire: null,
        enfantsM6: null,
        situation: null,
        dateDepot: null,
        dateDernierSuivi: null,
        isImported: null as 'oui' | null,
        isZonesDisplayed: null as 'oui' | null,
        showMyAffectationOnly: null as 'oui' | null,
        showMySignalementsOnly: null as 'oui' | null,
        isMessagePostCloture: null as 'oui' | null,
        isNouveauMessage: null as 'oui' | null,
        isMessageWithoutResponse: null as 'oui' | null,
        isDossiersSansActivite: null as 'oui' | null,
        isEmailAVerifier: null as 'oui' | null,
        isDossiersSansAgent: null as 'oui' | null,
        isActiviteRecente: null as 'oui' | null,
        showWithoutAffectationOnly: null as 'oui' | null,
        statusAffectation: null,
        criticiteScoreMin: null,
        criticiteScoreMax: null,
        motifCloture: null,
        relanceUsagerSansReponse: null as 'oui' | null
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
    ]
  },
  props: {
    ajaxurlSignalement: '',
    ajaxurlRemoveSignalement: '',
    ajaxurlExportCsv: '',
    ajaxurlSettings: '',
    ajaxurlContact: '',
    platformName: '',
    token: ''
  }
}

export const PATTERN_BADGE_EPCI = /\d{9}$/
