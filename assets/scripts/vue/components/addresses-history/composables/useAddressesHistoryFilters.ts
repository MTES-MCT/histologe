import { ref } from 'vue'
import { useAddressesHistoryApi } from '../api'
import { store } from './useAddressesHistoryStore'
import { variableTester } from '../../common/utils/variableTester'
import HistoInterfaceSelectOption from '../../common/HistoInterfaceSelectOption'
import type { AddressesResponse, SettingsResponse } from '../types'

export interface AddressesHistoryFilters {
  territoire: string | undefined
  adresse: string | undefined
  communes: string[]
  bailleurOuSyndic: string[]
  zone: string | undefined
  natureParc: string | undefined
  dossiersMultiples: string | undefined
  arreteTypes: string[]
}

export function useAddressesHistoryFilters() {
  const abortController = ref<AbortController | null>(null)
  const initialTerritoryId = ref<string>('')

  // Utilise le composable API moderne
  const api = useAddressesHistoryApi(store.props)

  /**
   * Recharge les settings (territoire, zones, communes, bailleurs)
   */
  const reloadSettings = async (): Promise<void> => {
    try {
      const territoryId = store.state.input.filters.territoire || undefined

      const response = await api.fetchSettings(territoryId)
      handleSettingsResponse(response)
    } catch (error) {
      console.error('Error reloading settings:', error)
      store.state.hasErrorLoading = true
      throw error
    }
  }

  /**
   * Traite la réponse des settings
   */
  const handleSettingsResponse = (response: SettingsResponse): void => {
    // User roles
    store.state.user.isAdmin = response.roleLabel === 'Super Admin'
    store.state.user.isResponsableTerritoire = response.roleLabel === 'Resp. Territoire'
    store.state.user.isAdministrateurPartenaire = response.roleLabel === 'Admin. partenaire'
    store.state.user.isAgent = ['Admin. partenaire', 'Agent'].includes(response.roleLabel || '')
    store.state.user.isMultiTerritoire = response.isMultiTerritoire === true

    store.state.territories = []
    if (response.territories) {
      for (const id in response.territories) {
        const optionItem = new HistoInterfaceSelectOption()
        const territory = response.territories[id]
        optionItem.Id = territory.id
        optionItem.Text = variableTester.isNotEmpty(territory)
          ? `${territory.zip} - ${territory.name}`
          : 'Territoire inconnu'
        store.state.territories.push(optionItem)
      }
    }

    store.state.addressesSuggestions = []
    if (response.addresses) {
      for (const id in response.addresses) {
        const address = response.addresses[id]
        if (variableTester.isNotEmpty(address) && address.address) {
          store.state.addressesSuggestions.push(address.address)
        }
      }
    }

    store.state.zones = []
    if (response.zones) {
      Object.values(response.zones).forEach((zone) => {
        const optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = zone.id.toString()
        optionItem.Text = zone.name
        store.state.zones.push(optionItem)
      })
    }

    store.state.bailleursAndSyndic = []
    if (response.bailleursSociaux) {
      for (const id in response.bailleursSociaux) {
        const bailleurName = response.bailleursSociaux[id]
        store.state.bailleursAndSyndic.push(bailleurName)
      }
    }

    store.state.communes = []
    if (response.communes) {
      for (const id in response.communes) {
        const commune = response.communes[id]
        store.state.communes.push(commune)
      }
    }
    // Ajout des EPCIs avec un préfixe pour les différencier
    if (response.epcis) {
      for (const id in response.epcis) {
        const epci = response.epcis[id]
        // Les EPCIs sont retournés comme des objets avec 'nom' et 'code'
        const epciName = typeof epci === 'object' && epci !== null ? epci.nom : epci
        store.state.communes.push(`EPCI : ${epciName}`)
      }
    }

    if (response.arreteTypes) {
      store.state.arreteTypesGroups = []
      for (const groupTitle in response.arreteTypes) {
        const arretes = response.arreteTypes[groupTitle]
        store.state.arreteTypesGroups.push({
          title: groupTitle,
          options: arretes
        })
      }
    }

    // Si plusieurs territoires existent et qu'aucun n'est sélectionné, sélectionner le premier automatiquement
    if (store.state.territories.length > 1 && !store.state.input.filters.territoire) {
      store.state.input.filters.territoire = store.state.territories[0].Id
    }
  }

  /**
   * Traite la réponse des adresses
   */
  const handleAddressesResponse = (response: AddressesResponse): void => {
    store.state.hasErrorLoading = false
    store.state.addresses.filters = (response as any).filters || {}
    store.state.addresses.list = (response as any).list || []

    // Normalise les filtres pour garantir que communes et arreteTypes sont des tableaux
    if (!Array.isArray(store.state.input.filters.communes)) {
      store.state.input.filters.communes = store.state.input.filters.communes
        ? [store.state.input.filters.communes as any]
        : []
    }
    if (!Array.isArray(store.state.input.filters.arreteTypes)) {
      store.state.input.filters.arreteTypes = store.state.input.filters.arreteTypes
        ? [store.state.input.filters.arreteTypes as any]
        : []
    }
    // Zone doit être une string ou undefined (pas un tableau)
    if (Array.isArray(store.state.input.filters.zone)) {
      store.state.input.filters.zone = store.state.input.filters.zone.length > 0
        ? store.state.input.filters.zone[0]
        : undefined
    }

    // Stocke la liste complète si on est en mode carte et sans filtres
    if (store.state.viewMode === 'map') {
      const hasFilters = Object.values(store.state.input.filters).some((value: any) => {
        if (Array.isArray(value)) return value.length > 0
        return value !== undefined && value !== ''
      })
      if (!hasFilters) {
        store.state.addresses.allAddresses = (response as any).list || []
      }
    }

    store.state.addresses.pagination = (response as any).pagination || store.state.addresses.pagination
    store.state.addresses.zoneAreas = (response as any).zoneAreas || []
    store.state.loadingSettings = false
    store.state.loadingList = false
  }

  /**
   * Recharge les données d'adresses avec les filtres actuels
   */
  const reloadAddresses = async (): Promise<void> => {
    try {
      // Annule la requête précédente si elle existe
      if (abortController.value) {
        abortController.value.abort()
        }

        store.state.loadingList = true

      abortController.value = new AbortController()

      updateUrlWithFilters()

      const response = await api.fetchAddresses({
        signal: abortController.value.signal
      })

      handleAddressesResponse(response)
    } catch (error: any) {
      if (error.name === 'AbortError' || error.message === 'Request cancelled') {
        return
      }

      console.error('Error reloading addresses:', error)
      store.state.hasErrorLoading = true
      store.state.loadingSettings = false
      store.state.loadingList = false
    }
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
        if (Array.isArray(value) && ['communes', 'bailleurOuSyndic', 'arreteTypes'].includes(key)) {
          value.forEach((item: any) => {
            addQueryParameter(key + '[]', item)
            url.searchParams.append(key + '[]', item)
          })
        } else if (typeof value === 'string' || typeof value === 'number') {
          addQueryParameter(key, value.toString())
          url.searchParams.set(key, value.toString())
        }
      } else {
        removeQueryParameter(key)
        url.searchParams.delete(key)
      }
    }

    const currentPage = store.state.addresses.pagination.current_page
    if (currentPage && currentPage > 1) {
      addQueryParameter('page', currentPage.toString())
      url.searchParams.set('page', currentPage.toString())
    }

    if (store.state.viewMode) {
      addQueryParameter('view', store.state.viewMode)
      url.searchParams.set('view', store.state.viewMode)
    }

    // Met à jour l'URL AJAX
    const queryParams = store.state.input.queryParameters
      .map((param: any) => `${param.name}=${param.value}`)
      .join('&')
    store.props.ajaxurlAddresses = store.props.baseAjaxUrlAddresses + '?' + queryParams

    // Met à jour l'URL du navigateur sans recharger la page
    window.history.replaceState({}, '', url.toString())
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
   * Initialise les filtres depuis les paramètres URL
   */
  const initFiltersFromUrl = (): void => {
    const urlParams = new URLSearchParams(window.location.search)

    if (urlParams.has('territoire')) {
      store.state.input.filters.territoire = urlParams.get('territoire') || undefined
    }

    if (urlParams.has('adresse')) {
      store.state.input.filters.adresse = urlParams.get('adresse') || undefined
    }

    const communes = urlParams.getAll('communes[]')
    if (communes.length > 0) {
      store.state.input.filters.communes = communes
    }

    const bailleurs = urlParams.getAll('bailleurOuSyndic[]')
    if (bailleurs.length > 0) {
      store.state.input.filters.bailleurOuSyndic = bailleurs
    }

    if (urlParams.has('zone')) {
      store.state.input.filters.zone = urlParams.get('zone') || undefined
    }

    if (urlParams.has('natureParc')) {
      store.state.input.filters.natureParc = urlParams.get('natureParc') || undefined
    }

    if (urlParams.has('dossiersMultiples')) {
      store.state.input.filters.dossiersMultiples = urlParams.get('dossiersMultiples') || undefined
    }

    const arreteTypes = urlParams.getAll('arreteTypes[]')
    if (arreteTypes.length > 0) {
      store.state.input.filters.arreteTypes = arreteTypes
    }

    if (urlParams.has('view')) {
      const viewMode = urlParams.get('view')
      if (viewMode === 'map') {
        store.state.viewMode = 'map' as any
      } else if (viewMode === 'list') {
        store.state.viewMode = 'list' as any
      }
    }

    if (urlParams.has('page')) {
      const page = parseInt(urlParams.get('page') || '1', 10)
      if (!isNaN(page) && page > 0) {
        store.state.addresses.pagination.current_page = page
      }
    }
  }

  /**
   * Obtient les filtres par défaut
   */
  const getDefaultFilters = (): AddressesHistoryFilters => ({
    territoire: undefined,
    adresse: undefined,
    communes: [],
    bailleurOuSyndic: [],
    zone: undefined,
    natureParc: undefined,
    dossiersMultiples: undefined,
    arreteTypes: []
  })

  /**
   * Réinitialise les filtres
   */
  const resetFilters = (): void => {
    store.state.input.filters = getDefaultFilters()
  }

  /**
   * Sauvegarde le territoire actuel (pour détecter les changements)
   */
  const saveCurrentTerritory = (): void => {
    initialTerritoryId.value = store.state.input.filters.territoire || ''
  }

  /**
   * Vérifie si le territoire a changé
   */
  const hasTerritoryChanged = (): boolean => {
    return initialTerritoryId.value !== (store.state.input.filters.territoire || '')
  }

  return {
    reloadSettings,
    reloadAddresses,
    resetFilters,
    initFiltersFromUrl,
    getDefaultFilters,
    saveCurrentTerritory,
    hasTerritoryChanged,
    handleSettingsResponse,
    handleAddressesResponse
  }
}
