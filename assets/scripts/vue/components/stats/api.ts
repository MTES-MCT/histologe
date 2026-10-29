import axios from 'axios'
import * as Sentry from '@sentry/browser'
import type { StatsFilters, StatsResponse } from './types'
import { toLocalIsoString } from './utils/dates'

/**
 * Construit le corps de la requête à partir des filtres
 */
function buildFormData(filters: StatsFilters, canFilterTerritoires: boolean): FormData {
  const data = new FormData()
  if (canFilterTerritoires) {
    data.append('territoire', filters.territoire)
  }
  data.append('communes', JSON.stringify(filters.communes))
  data.append('epcis', JSON.stringify(filters.epcis))
  data.append('statut', filters.statut)
  data.append('etiquettes', JSON.stringify(filters.etiquettes))
  data.append('type', filters.type)

  if (filters.dateRange !== null && filters.dateRange.length > 0) {
    data.append('dateStart', toLocalIsoString(filters.dateRange[0]))
    if (filters.dateRange.length > 1) {
      data.append('dateEnd', toLocalIsoString(filters.dateRange[1] ?? new Date()))
    }
  }

  data.append('countRefused', filters.countRefused ? '1' : '0')
  data.append('countArchived', filters.countArchived ? '1' : '0')

  return data
}

/**
 * Récupère les statistiques correspondant aux filtres
 */
export async function fetchStats(
  ajaxUrl: string,
  filters: StatsFilters,
  canFilterTerritoires: boolean,
  signal?: AbortSignal
): Promise<StatsResponse> {
  try {
    const response = await axios.post<StatsResponse>(
      ajaxUrl,
      buildFormData(filters, canFilterTerritoires),
      { timeout: 15000, signal }
    )
    return response.data
  } catch (error) {
    if (!axios.isCancel(error)) {
      console.error('Error fetching stats:', error)
      Sentry.captureException(error)
    }
    throw error
  }
}
