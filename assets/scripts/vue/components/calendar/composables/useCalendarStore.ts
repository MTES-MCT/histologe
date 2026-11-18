import { reactive } from 'vue'
import type { StoreState, StoreProps } from '../types'

const state = reactive<StoreState>({
  territories: [],
  operators: [],
  filters: {
    territoire: '',
    operateurs: [],
    uniquementMesDossiers: false,
  },
  loadingInit: true,
  hasErrorLoading: false,
})

const props = reactive<StoreProps>({
  ajaxurl: '',
})

export const store = { state, props }
