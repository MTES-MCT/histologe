import { reactive } from 'vue'
import type { ChartStats, StoreState, StoreProps } from '../types'

const emptyChart = (): ChartStats => ({ year: {}, all: {} })

// State
const state = reactive<StoreState>({
  filters: {
    territoire: 'all',
    yearTypes: {
      perMonth: 'year',
      perStatut: 'year',
      perMotifCloture: 'year',
      perDesordresCategories: 'year',
      perLogementDesordres: 'year',
      perBatimentDesordres: 'year'
    }
  },
  territoiresList: [],
  stats: {
    countSignalementResolus: 0,
    countSignalement: 0,
    countTerritory: 0,
    percentValidation: 0,
    percentCloture: 0,
    percentRefused: 0,
    countImported: 0,
    countSignalementPerTerritory: {},
    charts: {
      perMonth: emptyChart(),
      perStatut: emptyChart(),
      perMotifCloture: emptyChart(),
      perDesordresCategories: emptyChart(),
      perLogementDesordres: emptyChart(),
      perBatimentDesordres: emptyChart()
    }
  },
  loadingInit: true,
  loadingStats: false,
  hasErrorLoading: false
})

const props = reactive<StoreProps>({
  ajaxurl: '',
  platformName: ''
})

export const store = { state, props }

export const useFrontStatsStore = () => store
