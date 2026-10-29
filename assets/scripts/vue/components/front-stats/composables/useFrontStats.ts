import { fetchFrontStats } from '../api'
import { store } from './useFrontStatsStore'
import { mapFrontStats, toTerritoiresOptions } from '../services/frontStatsResponseMapper'

export function useFrontStats() {
  const state = store.state

  /**
   * Recharge les statistiques pour le territoire sélectionné
   */
  const reloadStats = async (): Promise<void> => {
    state.loadingStats = true

    try {
      const response = await fetchFrontStats(store.props.ajaxurl, state.filters.territoire)
      state.territoiresList = toTerritoiresOptions(response.list_territoires)
      state.stats = mapFrontStats(response)
      state.hasErrorLoading = false
    } catch {
      state.hasErrorLoading = true
    }

    state.loadingInit = false
    state.loadingStats = false
  }

  return {
    reloadStats
  }
}
