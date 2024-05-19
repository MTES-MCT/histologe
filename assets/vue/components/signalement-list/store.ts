import { QueryParameter } from './interfaces/queryParameter'
import { InputFilter } from './interfaces/inputFilter'
import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'

export const store = {
  state: {
    signalements: {
      filters: Object,
      list: new Array<Object>(),
      pagination: Object
    },
    input: {
      order: 'reference-DESC',
      queryParameters: [] as QueryParameter[],
      filters: {} as InputFilter
    },
    user: {
      isAdmin: false,
      isResponsableTerritoire: false,
      isAdministrateurPartenaire: false,
      isAgent: false,
      canSeeStatusAffectation: false,
      canDeleteSignalement: false,
      canSeeNonDecenceEnergetique: false,
      canSeeScore: false
    },
    territories: new Array<HistoInterfaceSelectOption>(),
    etiquettes: new Array<HistoInterfaceSelectOption>(),
    partenaires: new Array<HistoInterfaceSelectOption>(),
    communes: new Array<string>(),
    epcis: new Array<string>(),
    currentTerritoryId: '',
    currentCommmunes: '',
    currentPartenaires: '',
    statusSignalementList: [
      { Id: 'nouveau', Text: 'Nouveau' },
      { Id: 'en_cours', Text: 'En cours' },
      { Id: 'ferme', Text: 'Fermé' },
      { Id: 'refuse', Text: 'Refusé' }
    ],
    statusAffectationList: [
      { Id: 'accepte', Text: 'Accepté' },
      { Id: 'en_attente', Text: 'En attente' },
      { Id: 'refuse', Text: 'Refusé' },
      { Id: 'cloture_un_partenaire', Text: 'Clôturé par au moins un partenaire' },
      { Id: 'cloture_tous_partenaire', Text: 'Clôturé par tous les partenaires' }
    ],
    statusVisiteList: [
      { Id: 'Non planifiée', Text: 'Visite non planifiée' },
      { Id: 'Planifiée', Text: 'Visite planifiée' },
      { Id: 'Conclusion à renseigner', Text: 'Conclusion de visite à renseigner' },
      { Id: 'Terminée', Text: 'Visite terminée' }
    ],
    situationList: [
      { Id: 'attente_relogement', Text: 'Attente relogement' },
      { Id: 'bail_en_cours', Text: 'Bail en cours' },
      { Id: 'preavis_de_depart', Text: 'Préavis de départ' }
    ],
    procedureList: [
      { Id: 'non_decence_energetique', Text: 'Non décence énergétique' },
      { Id: 'non_decence', Text: 'Non décence' },
      { Id: 'rsd', Text: 'RSD' },
      { Id: 'danger', Text: 'Danger occupant' },
      { Id: 'insalubrite', Text: 'Insalubrité' },
      { Id: 'mise_en_securite_peril', Text: 'Péril' },
      { Id: 'suroccupation', Text: 'Suroccupation' }
    ],
    typeDernierSuiviList: [
      { Id: 'partenaire', Text: 'Suivi Partenaire' },
      { Id: 'usager', Text: 'Suivi Usager' },
      { Id: 'automatique', Text: 'Suivi Automatique' }
    ],
    typeDeclarantList: [
      { Id: 'locataire', Text: 'Occupant' },
      { Id: 'bailleur_occupant', Text: 'Bailleur occupant' },
      { Id: 'bailleur', Text: 'Tiers bailleurs' },
      { Id: 'tiers_particulier', Text: 'Tiers particulier' },
      { Id: 'tiers_pro', Text: 'Tiers professionnel' },
      { Id: 'service_secours', Text: 'Service de secours' }
    ],
    natureParcList: [
      { Id: 'privee', Text: 'Parc privée' },
      { Id: 'public', Text: 'Parc public' },
      { Id: 'non_renseigne', Text: 'Parc Non renseigné' }
    ],
    allocataireList: [
      { Id: 'non', Text: 'Non - Allocataire' },
      { Id: 'oui', Text: 'Oui - Allocataire' },
      { Id: 'caf', Text: 'CAF' },
      { Id: 'msa', Text: 'MSA' },
      { Id: 'non_renseigne', Text: 'Allocataire Non renseigné' }
    ],
    enfantMoinsSixList: [
      { Id: 'oui', Text: 'Oui - Enfant(s) moins de six ans' },
      { Id: 'non', Text: 'Non - Enfant(s) moins de six ans' },
      { Id: 'non_renseigne', Text: 'Non renseigné - Enfant(s) moins de six ans' }
    ]
  },
  props: {
    ajaxurlSignalement: '',
    ajaxurlRemoveSignalement: '',
    ajaxurlExportCsv: '',
    ajaxurlSettings: '',
    ajaxurlContact: ''
  }
}
