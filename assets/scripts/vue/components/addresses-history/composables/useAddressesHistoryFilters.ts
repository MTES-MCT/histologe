import { ref } from 'vue'
import { requests } from '../requests'
import { store } from '../store'
import { variableTester } from '../../common/utils/variableTester'
import HistoInterfaceSelectOption from '../../common/HistoInterfaceSelectOption'

export interface AddressesHistoryFilters {
  territoire: string | undefined
  adresse: string | undefined
  communes: string[]
  bailleurOuSyndic: string | undefined
  zones: string[]
  natureParc: string | undefined
  dossiersMultiples: string | undefined
  typesArretes: string[]
}

export function useAddressesHistoryFilters() {
  const abortController = ref<AbortController | null>(null)
  const initialTerritoryId = ref<string>('')

  /**
   * Recharge les settings (territoire, zones, communes, bailleurs)
   */
  const reloadSettings = async (): Promise<void> => {
    return new Promise((resolve) => {
      requests.getSettings((response: any) => {
        handleSettingsResponse(response)
        resolve()
      })
    })
  }

  /**
   * Traite la réponse des settings
   */
  const handleSettingsResponse = (response: any): void => {
    // User roles
    store.state.user.isAdmin = response.roleLabel === 'Super Admin'
    store.state.user.isResponsableTerritoire = response.roleLabel === 'Resp. Territoire'
    store.state.user.isAdministrateurPartenaire = response.roleLabel === 'Admin. partenaire'
    store.state.user.isAgent = ['Admin. partenaire', 'Agent'].includes(response.roleLabel)
    store.state.user.isMultiTerritoire = response.isMultiTerritoire === true

    // Territories
    store.state.territories = []
    for (const id in response.territories) {
      const optionItem = new HistoInterfaceSelectOption()
      optionItem.Id = response.territories[id].id
      const territory = response.territories[id]
      optionItem.Text = variableTester.isNotEmpty(territory)
        ? `${territory.zip} - ${territory.name}`
        : 'Territoire inconnu'
      store.state.territories.push(optionItem)
    }

    // Zones
    store.state.zones = []
    Object.values(response.zones).forEach((zone: any) => {
      const optionItem = new HistoInterfaceSelectOption()
      optionItem.Id = zone.id.toString()
      optionItem.Text = zone.name
      store.state.zones.push(optionItem)
    })

    // Bailleurs et syndics
    store.state.bailleursAndSyndic = []
    for (const id in response.bailleursSociaux) {
      const optionItem = new HistoInterfaceSelectOption()
      optionItem.Id = response.bailleursSociaux[id].id.toString()
      optionItem.Text = response.bailleursSociaux[id].name
      store.state.bailleursAndSyndic.push(optionItem)
    }

    // Communes
    store.state.communes = []
    for (const id in response.communes) {
      store.state.communes.push(response.communes[id])
    }
  }

  /**
   * Traite la réponse des adresses
   */
  const handleAddressesResponse = (response: any): void => {
    if (typeof response === 'string' && response === 'error') {
      store.state.hasErrorLoading = true
      store.state.loadingList = false
    } else {
      store.state.hasErrorLoading = false
      store.state.addresses.filters = response.filters
      store.state.addresses.list = response.list
      store.state.addresses.pagination = response.pagination
      store.state.addresses.zoneAreas = response.zoneAreas
      store.state.loadingList = false
    }
  }

  /**
   * Recharge les données d'adresses avec les filtres actuels
   */
  const reloadAddresses = (onSuccess: (response: any) => void): void => {
    // Annule la requête précédente si elle existe
    if (abortController.value) {
      abortController.value.abort()
    }

    abortController.value = new AbortController()

    // Met à jour l'URL avec les paramètres
    updateUrlWithFilters()

    // Lance la requête
    requests.getAddresses(onSuccess, { signal: abortController.value.signal })
  }

  /**
   * Met à jour l'URL avec les filtres actuels
   */
  const updateUrlWithFilters = (): void => {
    const url = new URL(globalThis.location.toString())
    url.search = ''
    store.state.input.queryParameters = []

    for (const [key, value] of Object.entries(store.state.input.filters)) {
      if (variableTester.isNotEmpty(value)) {
        if (Array.isArray(value) && ['communes', 'zones', 'typesArretes'].includes(key)) {
          value.forEach((item: any) => {
            addQueryParameter(key + '[]', item)
            url.searchParams.append(key + '[]', item)
          })
        } else if (typeof value === 'string') {
          addQueryParameter(key, value)
          url.searchParams.set(key, value)
        }
      } else {
        removeQueryParameter(key)
        url.searchParams.delete(key)
      }
    }

    // Met à jour l'URL AJAX
    const queryParams = store.state.input.queryParameters
      .map((param: any) => `${param.name}=${param.value}`)
      .join('&')
    store.props.ajaxurlAddresses = store.props.baseAjaxUrlAddresses + '?' + queryParams
  }

  /**
   * Ajoute un paramètre de requête
   */
  const addQueryParameter = (name: string, value: string): void => {
    const param = store.state.input.queryParameters.find(
      (p: any) => p.name === name && p.value === value
    )
    if (!param) {
      store.state.input.queryParameters.push({ name, value })
    }
  }

  /**
   * Supprime un paramètre de requête
   */
  const removeQueryParameter = (name: string): void => {
    const index = store.state.input.queryParameters.findIndex((p: any) => p.name === name)
    if (index !== -1) {
      store.state.input.queryParameters.splice(index, 1)
    }
  }

  /**
   * Obtient les filtres par défaut
   */
  const getDefaultFilters = (): AddressesHistoryFilters => ({
    territoire: undefined,
    adresse: undefined,
    communes: [],
    bailleurOuSyndic: undefined,
    zones: [],
    natureParc: undefined,
    dossiersMultiples: undefined,
    typesArretes: []
  })

  /**
   * Réinitialise les filtres
   */
  const resetFilters = (): void => {
    store.state.input.filters = getDefaultFilters()
    store.state.currentTerritoryId = ''
  }

  /**
   * Sauvegarde le territoire actuel (pour détecter les changements)
   */
  const saveCurrentTerritory = (): void => {
    initialTerritoryId.value = store.state.currentTerritoryId
  }

  /**
   * Vérifie si le territoire a changé
   */
  const hasTerritoryChanged = (): boolean => {
    return initialTerritoryId.value !== store.state.currentTerritoryId
  }

  return {
    reloadSettings,
    reloadAddresses,
    resetFilters,
    getDefaultFilters,
    saveCurrentTerritory,
    hasTerritoryChanged,
    handleSettingsResponse,
    handleAddressesResponse
  }
}
