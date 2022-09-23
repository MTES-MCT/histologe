import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'

export const store = {
  state: {
    filters: {
      perMonthYearType: 'year',
      perStatutYearType: 'year',
      perSituationYearType: 'year',
      perMotifClotureYearType: 'year',
      territoire: 'all',
      territoiresList: new Array<HistoInterfaceSelectOption>()
    },
    stats: {
      countSignalement: 0,
      countTerritory: 0,
      percentValidation: 0,
      percentCloture: 0,
      countSignalementPerTerritory: Object,
      countSignalementPerMonth: Object,
      countSignalementPerStatut: Object,
      countSignalementPerSituation: Object,
      countSignalementPerMotifCloture: Object,
      countSignalementPerMonthThisYear: Object,
      countSignalementPerStatutThisYear: Object,
      countSignalementPerSituationThisYear: Object,
      countSignalementPerMotifClotureThisYear: Object
    }
  },
  props: {
    ajaxurl: ''
  }
}
