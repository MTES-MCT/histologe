<template>
  <FullCalendar ref="calendar" :options="calendarOptions" />
</template>

<script setup lang="ts">
import { reactive, useTemplateRef, watch } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import type { CalendarOptions, DatesSetInfo } from '@fullcalendar/vue3'
import themePlugin from '@fullcalendar/vue3/themes/classic'
import dayGridPlugin from '@fullcalendar/vue3/daygrid'
import listPlugin from '@fullcalendar/vue3/list'
import frLocale from '@fullcalendar/vue3/locales/fr'
import { dsfrOptions } from '../services/fullCalendarDsfrOptions'
import { store } from '../composables/useCalendarStore'
import { useCalendarEvents } from '../composables/useCalendarEvents'

const calendar = useTemplateRef<InstanceType<typeof FullCalendar>>('calendar')
const { fetchCalendarEvents } = useCalendarEvents()

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
const onDatesSet = (info: DatesSetInfo): void => {
  calendarOptions.toolbarElements = {
    ...calendarOptions.toolbarElements,
    weekBadge: { html: buildWeekBadge(info) },
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
  datesSet: onDatesSet,
})

// Recharge les événements quand les filtres changent
watch(
  () => store.state.filters,
  () => {
    calendar.value?.getApi().refetchEvents()
  },
  { deep: true }
)
</script>
