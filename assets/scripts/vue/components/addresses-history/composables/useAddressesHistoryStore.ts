import { ref, computed, readonly } from 'vue'
import type HistoInterfaceSelectOption from '../../common/HistoInterfaceSelectOption'
import type {
  StoreState,
  StoreProps,
  ViewMode,
  Filters,
  Pagination
} from '../types'
import {
  NatureParc,
  DossiersMultiples,
  ArreteType
} from '../types'

// State
const state = ref<StoreState>({
  addresses: {
    filters: {},
    list: [],
    allAddresses: [],
    pagination: {
      current_page: 1,
      total_pages: 1,
      total_items: 0,
      per_page: 10,
    },
    zoneAreas: [],
  },
  input: {
    order: 'reference-DESC',
    queryParameters: [],
    filters: {
      territoire: undefined,
      adresse: undefined,
      communes: [],
      bailleurOuSyndic: undefined,
      zone: undefined,
      natureParc: undefined,
      dossiersMultiples: undefined,
      arreteTypes: [],
    }
  },
  user: {
    isAdmin: false,
    isResponsableTerritoire: false,
    isAdministrateurPartenaire: false,
    isAgent: false,
    isMultiTerritoire: false,
    partnerIds: []
  },
  territories: [],
  addressesSuggestions: [],
  communes: [],
  bailleursAndSyndic: [],
  zones: [],
  currentTerritoryId: '',
  currentCommunes: '',
  viewMode: 'list' as ViewMode,
  loadingList: true,
  hasErrorLoading: false,
  natureParcList: [
    { Id: NatureParc.Privee, Text: 'Parc privé' },
    { Id: NatureParc.Public, Text: 'Parc public' },
    { Id: NatureParc.NonRenseigne, Text: 'Parc Non renseigné' }
  ],
  dossiersMultiplesList: [
    { Id: DossiersMultiples.All, Text: 'Tout' },
    { Id: DossiersMultiples.Avec, Text: 'Avec' },
    { Id: DossiersMultiples.Sans, Text: 'Sans' },
  ],
  arretesTypesGroups: [],
  filtersApplyKey: 0
})

const props = ref<StoreProps>({
  ajaxurlAddresses: '',
  baseAjaxUrlAddresses: '',
  ajaxurlExportCsv: '',
  ajaxurlSettings: '',
  platformName: '',
  token: ''
})

// Getters (computed)
const hasActiveFilters = computed(() => {
  const filters = state.value.input.filters
  return !!(
    filters.territoire ||
    filters.adresse ||
    filters.communes.length > 0 ||
    filters.bailleurOuSyndic ||
    filters.zone ||
    filters.natureParc ||
    filters.dossiersMultiples ||
    filters.arreteTypes.length > 0
  )
})

const isMapView = computed(() => state.value.viewMode === 'map')
const isListView = computed(() => state.value.viewMode === 'list')

const totalAddresses = computed(() => state.value.addresses.pagination.total_items)

const canExport = computed(() =>
  state.value.addresses.list.length > 0 && !state.value.loadingList
)

// Actions
function setViewMode(mode: ViewMode): void {
  state.value.viewMode = mode
}

function setLoadingState(loading: boolean): void {
  state.value.loadingList = loading
}

function setErrorState(hasError: boolean): void {
  state.value.hasErrorLoading = hasError
}

function setAddresses(data: {
  list: Array<Record<string, unknown>>
  allAddresses: Array<Record<string, unknown>>
  pagination: Pagination
  zoneAreas: string[]
}): void {
  state.value.addresses = {
    filters: state.value.addresses.filters,
    ...data
  }
}

function setFilters(filters: Partial<Filters>): void {
  state.value.input.filters = {
    ...state.value.input.filters,
    ...filters
  }
}

function resetFilters(): void {
  state.value.input.filters = {
    territoire: undefined,
    adresse: undefined,
    communes: [],
    bailleurOuSyndic: undefined,
    zone: undefined,
    natureParc: undefined,
    dossiersMultiples: undefined,
    arreteTypes: [],
  }
  state.value.filtersApplyKey++
}

function setTerritories(territories: HistoInterfaceSelectOption[]): void {
  state.value.territories = territories
}

function setCommunes(communes: string[]): void {
  state.value.communes = communes
}

function setBailleursAndSyndic(bailleursAndSyndic: string[]): void {
  state.value.bailleursAndSyndic = bailleursAndSyndic
}

function setZones(zones: HistoInterfaceSelectOption[]): void {
  state.value.zones = zones
}

function setCurrentTerritoryId(id: string): void {
  state.value.currentTerritoryId = id
}

function setCurrentCommunes(communes: string): void {
  state.value.currentCommunes = communes
}

function setPagination(pagination: Partial<Pagination>): void {
  state.value.addresses.pagination = {
    ...state.value.addresses.pagination,
    ...pagination
  }
}

function setProps(newProps: Partial<StoreProps>): void {
  props.value = {
    ...props.value,
    ...newProps
  }
}

function setUserInfo(userInfo: Partial<StoreState['user']>): void {
  state.value.user = {
    ...state.value.user,
    ...userInfo
  }
}

// Store export
export const useAddressesHistoryStore = () => ({
  // State (readonly pour éviter les mutations directes)
  state: readonly(state),
  props: readonly(props),

  // Getters
  hasActiveFilters,
  isMapView,
  isListView,
  totalAddresses,
  canExport,

  // Actions
  setViewMode,
  setLoadingState,
  setErrorState,
  setAddresses,
  setFilters,
  resetFilters,
  setTerritories,
  setCommunes,
  setBailleursAndSyndic,
  setZones,
  setCurrentTerritoryId,
  setCurrentCommunes,
  setPagination,
  setProps,
  setUserInfo,
})

// Backward compatibility - export old store format
export const store = {
  get state() {
    return state.value
  },
  get props() {
    return props.value
  }
}
