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
  },
  getTextFromList (id: any, context: string | null): string | undefined {
    if (context === 'territoires') {
      return this.state.territories.find(territory => territory.Id.toString() === id)?.Text
    }

    if ((context === 'communes' || context === 'epcis') && typeof id !== 'undefined') {
      return id.join(', ')
    }

    if (context === 'enfantsM6') {
      const item = this.state.enfantMoinsSixList.find(item => item.Id === id)
      if (item != null) {
        return item.Text
      }
    }

    if (context === 'allocataire') {
      const item = this.state.allocataireList.find(item => item.Id === id)
      if (item != null) {
        return item.Text
      }
    }

    if (context === 'criticiteScoreMin') {
      return `Criticité Minimum : ${id}`
    }

    if (context === 'criticiteScoreMax') {
      return `Criticité Maximum : ${id}`
    }

    if (context === 'dateDernierSuivi' || context === 'dateDepot') {
      let label: string = ''
      let startDate: string = ''
      let endDate: string = ''

      startDate = id[0].toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
      endDate = id[1].toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })

      if (context === 'dateDernierSuivi') {
        label = 'Date de dernier suivi : '
      }

      if (context === 'dateDepot') {
        label = 'Date de dépôt : '
      }

      return `${label} ${startDate} - ${endDate}`
    }

    const staticListsWithNoDuplicateId = [
      this.state.statusSignalementList,
      this.state.statusAffectationList,
      this.state.statusVisiteList,
      this.state.situationList,
      this.state.procedureList,
      this.state.typeDernierSuiviList,
      this.state.typeDeclarantList,
      this.state.natureParcList
    ]

    for (const list of staticListsWithNoDuplicateId) {
      const item = list.find(item => item.Id === id)
      if (item != null) {
        return item.Text
      }
    }

    return id
  }
}
