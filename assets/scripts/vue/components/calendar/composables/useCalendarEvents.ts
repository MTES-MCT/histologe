import axios from 'axios'
import type { EventInput, EventSourceFuncInfo } from '@fullcalendar/vue3'
import { fetchEvents } from '../api'
import { store } from './useCalendarStore'

// Partagé entre les instances : une nouvelle requête annule la précédente
let abortController: AbortController | null = null

export function useCalendarEvents() {
  const state = store.state

  /**
   * Source d'événements de FullCalendar, appelée à chaque changement de période
   * et à chaque refetchEvents() (changement de filtres)
   */
  const fetchCalendarEvents = async (info: EventSourceFuncInfo): Promise<EventInput[]> => {
    abortController?.abort()
    abortController = new AbortController()

    try {
      const events = await fetchEvents(
        store.props.ajaxurl,
        state.filters,
        // "2026-10-26T00:00:00+01:00" → "2026-10-26"
        { dateDebut: info.startStr.slice(0, 10), dateFin: info.endStr.slice(0, 10) },
        abortController.signal
      )
      state.loadingInit = false
      return events
    } catch (error) {
      // Requête remplacée par une plus récente : FullCalendar ignore son résultat
      if (!axios.isCancel(error)) {
        state.loadingInit = false
        state.hasErrorLoading = true
      }
      return []
    }
  }

  return {
    fetchCalendarEvents,
  }
}
