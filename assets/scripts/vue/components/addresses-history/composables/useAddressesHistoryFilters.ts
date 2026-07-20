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
  bailleurOuSyndic: string | undefined
  zone: string | undefined
  natureParc: string | undefined
  dossiersMultiples: string | undefined
  typesArretes: string[]
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
      const territoryId = store.state.currentTerritoryId.length > 0
        ? store.state.currentTerritoryId
        : undefined

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

    // Territories
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

    // Zones
    store.state.zones = []
    if (response.zones) {
      Object.values(response.zones).forEach((zone) => {
        const optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = zone.id.toString()
        optionItem.Text = zone.name
        store.state.zones.push(optionItem)
      })
    }

    // Bailleurs et syndics
    store.state.bailleursAndSyndic = []
    if (response.bailleursSociaux) {
      for (const id in response.bailleursSociaux) {
        const bailleur = response.bailleursSociaux[id]
        store.state.bailleursAndSyndic.push(bailleur.name)
      }
    }

    // Communes
    store.state.communes = []
    if (response.communes) {
      for (const id in response.communes) {
        const commune = response.communes[id]
        store.state.communes.push(commune)
      }
    }

    // Types d'arrêtés
    if (response.typesArretes) {
      store.state.typesArretesGroups = []
      for (const groupTitle in response.typesArretes) {
        const arretes = response.typesArretes[groupTitle]
        store.state.typesArretesGroups.push({
          title: groupTitle,
          options: arretes
        })
      }
    }
  }

  /**
   * Traite la réponse des adresses
   */
  const handleAddressesResponse = (response: AddressesResponse): void => {
    store.state.hasErrorLoading = false
    store.state.addresses.filters = (response as any).filters || {}
    store.state.addresses.list = (response as any).list || []

    // Normalise les filtres pour garantir que communes et typesArretes sont des tableaux
    if (!Array.isArray(store.state.input.filters.communes)) {
      store.state.input.filters.communes = store.state.input.filters.communes
        ? [store.state.input.filters.communes as any]
        : []
    }
    if (!Array.isArray(store.state.input.filters.typesArretes)) {
      store.state.input.filters.typesArretes = store.state.input.filters.typesArretes
        ? [store.state.input.filters.typesArretes as any]
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

      abortController.value = new AbortController()

      // Met à jour l'URL avec les paramètres
      updateUrlWithFilters()

      // Lance la requête avec le composable API
      const response = await api.fetchAddresses({
        signal: abortController.value.signal
      })

      handleAddressesResponse(response)
    } catch (error: any) {
      // Ignore les erreurs d'annulation
      if (error.name === 'AbortError' || error.message === 'Request cancelled') {
        return
      }

      console.error('Error reloading addresses:', error)
      store.state.hasErrorLoading = true
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
        if (Array.isArray(value) && ['communes', 'typesArretes'].includes(key)) {
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
    zone: undefined,
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
