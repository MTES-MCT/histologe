import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'

export const store = {
  state: {
    filters: {
      communesList: new Array<HistoInterfaceSelectOption>(),
      communes: new Array<string>(),
      statut: 'all',
      etiquettesList: new Array<HistoInterfaceSelectOption>(),
      etiquettes: new Array<string>(),
      type: 'all',
      dateRange: new Array<Date>(),
      countRefused: false,
      countArchived: false,
      territoire: 'all',
      canFilterTerritoires: false,
      canFilterArchived: false,
      canSeePerPartenaire: false,
      territoiresList: new Array<HistoInterfaceSelectOption>()
    },
    stats: {
      countSignalement: 0,
      averageCriticite: 0,
      averageDaysValidation: 0,
      averageDaysClosure: 0,
      countSignalementsRefuses: 0,
      countSignalementsArchives: 0,
      countSignalementFiltered: 0,
      averageCriticiteFiltered: 0,
      countSignalementPerMonth: Object,
      countSignalementPerPartenaire: new Array<any>(),
      countSignalementPerSituation: Object,
      countSignalementPerCriticite: Object,
      countSignalementPerStatut: Object,
      countSignalementPerCriticitePercent: Object,
      countSignalementPerVisite: Object,
      countSignalementPerMotifCloture: Object
    }
  },
  props: {
    ajaxurl: ''
  }
}
