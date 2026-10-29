import axios from 'axios'
import { fetchStats } from '../api'
import { store } from './useStatsStore'
import { mapFiltersOptions, mapStats } from '../services/statsResponseMapper'
import { getDefaultDateRange } from '../utils/dates'

// Partagé entre les instances : une nouvelle requête annule la précédente
let abortController: AbortController | null = null

export function useStatsFilters() {
  const state = store.state

  /**
   * Recharge les statistiques (et les options des filtres) avec les filtres actuels
   * Retourne false si la requête a été annulée ou a échoué
   */
  const reloadStats = async (reinit: boolean = false): Promise<boolean> => {
    abortController?.abort()
    abortController = new AbortController()

    state.loadingInit = reinit
    state.loadingStats = true

    try {
      const response = await fetchStats(
        store.props.ajaxurl,
        state.filters,
        state.options.canFilterTerritoires,
        abortController.signal
      )
      state.options = mapFiltersOptions(response)
      state.stats = mapStats(response)
      state.hasErrorLoading = false
    } catch (error) {
      if (axios.isCancel(error)) {
        return false
      }
      state.hasErrorLoading = true
    }

    state.loadingInit = false
    state.loadingStats = false

    return !state.hasErrorLoading
  }

  /**
   * Initialise la plage de dates par défaut
   */
  const initDates = (): void => {
    state.filters.dateRange = getDefaultDateRange()
  }

  /**
   * Vide les filtres dont les options dépendent du territoire
   */
  const clearTerritoryDependentFilters = (): void => {
    state.filters.communes = []
    state.filters.epcis = []
    state.filters.etiquettes = []
  }

  /**
   * Réinitialise les filtres (le territoire sélectionné est conservé)
   */
  const resetFilters = (): void => {
    clearTerritoryDependentFilters()
    state.filters.statut = 'all'
    state.filters.type = 'all'
    state.filters.countRefused = false
    state.filters.countArchived = false
    initDates()
  }

  return {
    reloadStats,
    initDates,
    clearTerritoryDependentFilters,
    resetFilters
  }
}
