import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'

export const store = {
  state: {
    filters: {
      perMonthYearType: 'year',
      perStatutYearType: 'year',
      perMotifClotureYearType: 'year',
      perDesordresCategoriesYearType: 'year',
      perLogementDesordresYearType: 'year',
      perBatimentDesordresYearType: 'year',
      territoire: 'all',
      territoiresList: new Array<HistoInterfaceSelectOption>()
    },
    stats: {
      countSignalementResolus: 0,
      countSignalement: 0,
      countTerritory: 0,
      percentValidation: 0,
      percentCloture: 0,
      percentRefused: 0,
      countImported: 0,
      countSignalementPerTerritory: Object,
      countSignalementPerMonth: Object,
      countSignalementPerStatut: Object,
      countSignalementPerMotifCloture: Object,
      countSignalementPerDesordresCategories: Object,
      countSignalementPerLogementDesordres: Object,
      countSignalementPerBatimentDesordres: Object,
      countSignalementPerMonthThisYear: Object,
      countSignalementPerStatutThisYear: Object,
      countSignalementPerMotifClotureThisYear: Object,
      countSignalementPerDesordresCategoriesThisYear: Object,
      countSignalementPerLogementDesordresThisYear: Object,
      countSignalementPerBatimentDesordresThisYear: Object
    }
  },
  props: {
    ajaxurl: ''
  }
}
