<template>
  <FullCalendar ref="calendar" :options="calendarOptions" />
  <CalendarEventPopover
    v-if="selectedEvent"
    :key="selectedEvent.key"
    :event="selectedEvent.event"
    :anchor="selectedEvent.anchor"
    @close="closeEventPopover"
  />
</template>

<script setup lang="ts">
import { reactive, shallowRef, useTemplateRef, watch } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import type { CalendarOptions, DatesSetInfo, EventApi, EventClickInfo } from '@fullcalendar/vue3'
import themePlugin from '@fullcalendar/vue3/themes/classic'
import dayGridPlugin from '@fullcalendar/vue3/daygrid'
import listPlugin from '@fullcalendar/vue3/list'
import frLocale from '@fullcalendar/vue3/locales/fr'
import { dsfrOptions } from '../services/fullCalendarDsfrOptions'
import { store } from '../composables/useCalendarStore'
import { useCalendarEvents } from '../composables/useCalendarEvents'
import CalendarEventPopover from './CalendarEventPopover.vue'

interface SelectedEvent {
  // Change à chaque ouverture : la popup est recréée et repositionnée
  key: number
  event: EventApi
  anchor: HTMLElement
}

const calendar = useTemplateRef<InstanceType<typeof FullCalendar>>('calendar')
const { fetchCalendarEvents } = useCalendarEvents()

// Événement dont la popup de détail est ouverte
// (shallowRef : les objets FullCalendar n'ont pas à être rendus réactifs en profondeur)
const selectedEvent = shallowRef<SelectedEvent | null>(null)
let selectionKey = 0

// Vues pour lesquelles le numéro de semaine est affiché dans la barre d'outils
const WEEK_VIEWS = ['dayGridWeek', 'listWeek']

/**
 * Construit le badge du numéro de semaine (vide hors vues semaine)
 */
const buildWeekBadge = (info: DatesSetInfo): string => {
  if (!WEEK_VIEWS.includes(info.view.type)) {
    return ''
  }

  // Formaté selon la locale du calendrier : "Semaine 46"
  const weekLabel = info.view.calendar.formatDate(info.view.currentStart, { week: 'long' })
  return `<p class="fr-badge calendar-app__week-badge">${weekLabel}</p>`
}

/**
 * Met à jour le badge du numéro de semaine à chaque changement de vue ou de période
 */
const closeEventPopover = (): void => {
  selectedEvent.value = null
}
const onDatesSet = (info: DatesSetInfo): void => {
  closeEventPopover()
  calendarOptions.toolbarElements = {
    ...calendarOptions.toolbarElements,
    weekBadge: { html: buildWeekBadge(info) },
  }
}


/**
 * Élément auquel ancrer la popup de détail
 * Événement cliqué dans la popup "+ x autres" : celle-ci est fermée (la popup de détail la remplace)
 * et la popup de détail est ancrée au lien "+ x autres"
 */
const getEventAnchor = (eventElement: HTMLElement): HTMLElement => {
  const morePopover = eventElement.closest<HTMLElement>('.calendar-app__popover')
  if (!morePopover) {
    return eventElement
  }

  const moreLink = document.querySelector<HTMLElement>(`[aria-controls="${morePopover.id}"]`)
  // FullCalendar ferme sa popup sur la touche Échap
  document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
  return moreLink ?? eventElement
}

/**
 * Clic sur un événement : ouvre sa popup de détail
 */
const onEventClick = (info: EventClickInfo): void => {
  // Vue liste : toutes les informations et le lien sont déjà affichés, pas de popup
  // (un clic sur le lien "Voir le dossier" / "S'inscrire" garde sa navigation normale)
  if (info.view.type.startsWith('list')) {
    return
  }

  info.jsEvent.preventDefault()
  selectionKey++
  selectedEvent.value = {
    key: selectionKey,
    event: info.event,
    anchor: getEventAnchor(info.el),
  }
}

// Options du calendrier (réactives pour mettre à jour le badge de semaine)
const calendarOptions: CalendarOptions = reactive({
  ...dsfrOptions,
  plugins: [themePlugin, dayGridPlugin, listPlugin],
  initialView: 'dayGridMonth',
  locale: frLocale,
  // Chargés par FullCalendar pour la période affichée (jours des mois voisins compris)
  events: fetchCalendarEvents,
  hiddenDays: [0],
  headerToolbar: {
    left: 'prev next title weekBadge',
    center: '',
    right: 'viewLabel dayGridMonth,dayGridWeek,listWeek',
  },
  // Éléments personnalisés utilisables dans headerToolbar
  toolbarElements: {
    viewLabel: 'Affichage :',
    weekBadge: { html: '' },
  },
  // Surcharge du libellé "Planning" de la locale fr
  listText: 'Liste',
  // Textes accessibles absents de la locale fr (sinon en anglais : "Close", "Events")
  closeHint: 'Fermer',
  eventsHint: 'Événements',
  datesSet: onDatesSet,
  eventClick: onEventClick,
})

// Recharge les événements quand les filtres changent
watch(
  () => store.state.filters,
  () => {
    closeEventPopover()
    calendar.value?.getApi().refetchEvents()
  },
  { deep: true }
)
</script>
