import axios from 'axios'
import * as Sentry from '@sentry/browser'
import type { EventInput } from '@fullcalendar/vue3'
import type { CalendarFilters, CalendarPeriod } from './types'
import { AXIOS_TIMEOUT } from '../../../vanilla/controllers/environment'

/**
 * Construit le corps de la requête à partir des filtres et de la période affichée
 */
function buildFormData(filters: CalendarFilters, period: CalendarPeriod): FormData {
  const data = new FormData()
  if (filters.territoire !== '') {
    data.append('territoire', filters.territoire)
  }
  filters.operateurs.forEach((id) => data.append('operateurs[]', id))
  data.append('uniquementMesDossiers', filters.uniquementMesDossiers ? '1' : '0')
  data.append('dateDebut', period.dateDebut)
  data.append('dateFin', period.dateFin)

  return data
}

/**
 * Récupère les événements correspondant aux filtres sur la période affichée
 */
export async function fetchEvents(
  ajaxUrl: string,
  filters: CalendarFilters,
  period: CalendarPeriod,
  signal?: AbortSignal
): Promise<EventInput[]> {
  try {
    const response = await axios.post<EventInput[]>(
      ajaxUrl,
      buildFormData(filters, period),
      { timeout: AXIOS_TIMEOUT, signal }
    )
    return response.data
  } catch (error) {
    if (!axios.isCancel(error)) {
      console.error('Error fetching events:', error)
      Sentry.captureException(error)
    }
    throw error
  }
}
