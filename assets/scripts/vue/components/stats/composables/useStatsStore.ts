import { reactive } from 'vue'
import type { StoreState, StoreProps } from '../types'

// State
const state = reactive<StoreState>({
  filters: {
    communes: [],
    epcis: [],
    statut: 'all',
    etiquettes: [],
    type: 'all',
    dateRange: [],
    countRefused: false,
    countArchived: false,
    territoire: 'all'
  },
  options: {
    communesList: [],
    epcisList: [],
    etiquettesList: [],
    territoiresList: [],
    canFilterTerritoires: false,
    canFilterArchived: false,
    canSeePerPartenaire: false
  },
  stats: {
    countSignalementPerMonth: {},
    countSignalementPerPartenaire: [],
    countSignalementPerSituation: {},
    countSignalementPerCriticite: {},
    countSignalementPerStatut: {},
    countSignalementPerCriticitePercent: {},
    countSignalementPerVisite: {},
    countSignalementPerMotifCloture: {}
  },
  loadingInit: true,
  loadingStats: false,
  hasErrorLoading: false
})

const props = reactive<StoreProps>({
  ajaxurl: ''
})

export const store = { state, props }

export const useStatsStore = () => store
