<template>
  <div class="calendar-app">
    <h1>Calendrier</h1>
    <FullCalendar :options="calendarOptions" />
  </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import type {
  ButtonGroupInfo,
  ButtonInfo,
  CalendarOptions,
  DatesSetInfo,
  DayCellInfo,
  DayHeaderInfo,
  InlineWeekNumberInfo,
  ListDayHeaderInnerInfo,
  ListDayInfo,
} from '@fullcalendar/vue3'
import themePlugin from '@fullcalendar/vue3/themes/classic'
import dayGridPlugin from '@fullcalendar/vue3/daygrid'
import listPlugin from '@fullcalendar/vue3/list'
import frLocale from '@fullcalendar/vue3/locales/fr'
import { FAKE_EVENTS } from './fakeEvents'

import '@fullcalendar/vue3/skeleton.css'
import '@fullcalendar/vue3/themes/classic/theme.css'
import '@fullcalendar/vue3/themes/classic/palette.css'

// Vues pour lesquelles le numéro de semaine est affiché
const WEEK_VIEWS = ['dayGridWeek', 'listWeek']

// Bornes d'une période à formater (dates "marqueurs" FullCalendar, codées en UTC)
interface TitleRange {
  start: { marker: Date }
  end?: { marker: Date } | null
}

const dayFormatter = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', timeZone: 'UTC' })
const dayMonthFormatter = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', timeZone: 'UTC' })
const fullDateFormatter = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' })

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
 * Classes des boutons : le groupe de choix de vue est stylé comme un contrôle segmenté DSFR
 */
const getButtonClass = (info: ButtonInfo): string => {
  if (!info.buttonGroup?.hasSelection) {
    return ''
  }
  if (info.isSelected) {
    return 'calendar-app__view-button calendar-app__view-button--selected'
  }
  return 'calendar-app__view-button'
}

/**
 * Classe du groupe de boutons de choix de vue
 */
const getButtonGroupClass = (info: ButtonGroupInfo): string => {
  return info.hasSelection ? 'calendar-app__view-buttons' : ''
}

/**
 * Classes des cellules de jour (jours des mois voisins grisés)
 */
const getDayCellClass = (info: DayCellInfo): string => {
  return info.isOther ? 'calendar-app__day calendar-app__day--other' : 'calendar-app__day'
}

/**
 * Classes du numéro de jour (pastille pour le jour courant)
 */
const getDayNumberClass = (info: DayCellInfo): string => {
  if (info.isToday) {
    return 'calendar-app__day-number calendar-app__today-pill'
  }
  return 'calendar-app__day-number'
}

/**
 * Classes d'un jour de la vue liste (mise en avant du jour courant)
 */
const getListDayClass = (info: ListDayInfo): string => {
  return info.isToday ? 'calendar-app__list-day calendar-app__list-day--today' : 'calendar-app__list-day'
}

/**
 * Classes des textes de l'en-tête d'un jour de la vue liste : jour de la semaine (niveau 0) puis date
 */
const getListDayHeaderInnerClass = (info: ListDayHeaderInnerInfo): string => {
  return info.level === 0 ? 'calendar-app__list-day-weekday' : 'calendar-app__list-day-date'
}

/**
 * Libellé court du numéro de semaine dans la vue mois : "S45"
 */
const getInlineWeekNumberContent = (info: InlineWeekNumberInfo): string => {
  return `S${info.num}`
}

/**
 * Titre de la vue liste : "2 au 7 novembre 2026", "30 novembre au 5 décembre 2026"
 * ou "28 décembre 2026 au 2 janvier 2027"
 */
const formatListTitle = (range: TitleRange): string => {
  const start = range.start.marker
  const end = range.end?.marker ?? start
  const endLabel = fullDateFormatter.format(end)

  if (start.getUTCFullYear() !== end.getUTCFullYear()) {
    return `${fullDateFormatter.format(start)} au ${endLabel}`
  }
  if (start.getUTCMonth() !== end.getUTCMonth()) {
    return `${dayMonthFormatter.format(start)} au ${endLabel}`
  }
  return `${dayFormatter.format(start)} au ${endLabel}`
}

/**
 * En-tête de jour de la vue semaine : numéro du jour courant dans une pastille
 */
const getWeekDayHeaderContent = (info: DayHeaderInfo): { html: string } | true => {
  if (!info.isToday) {
    return true
  }

  const html = info.textParts
    .map((part) => (part.type === 'day' ? `<span class="calendar-app__today-pill">${part.value}</span>` : part.value))
    .join('')
  return { html }
}

/**
 * Numéro de jour : mois abrégé pour les jours des mois voisins ("1 déc.")
 */
const getDayCellTopContent = (info: DayCellInfo): string | true => {
  if (!info.isOther) {
    return true
  }

  return info.view.calendar.formatDate(info.date, { day: 'numeric', month: 'short' })
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
  plugins: [themePlugin, dayGridPlugin, listPlugin],
  initialView: 'dayGridMonth',
  locale: frLocale,
  events: FAKE_EVENTS,
  hiddenDays: [0],
  views: {
    // Numéro de semaine dans la première cellule de chaque ligne (le lundi)
    dayGridMonth: {
      weekNumbers: true,
    },
    // En-têtes avec la date : "Lundi 2"
    dayGridWeek: {
      dayHeaderFormat: { weekday: 'long', day: 'numeric' },
      dayHeaderContent: getWeekDayHeaderContent,
    },
    // Colonne de gauche : "Lundi" puis "2 novembre 2026"
    listWeek: {
      titleFormat: formatListTitle,
      listDayFormat: { weekday: 'long' },
      listDayAltFormat: { day: 'numeric', month: 'long', year: 'numeric' },
    },
  },
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
  // Noms des jours en entier dans l'en-tête ("lundi")
  dayHeaderFormat: { weekday: 'long' },
  inlineWeekNumberContent: getInlineWeekNumberContent,
  dayCellTopContent: getDayCellTopContent,
  // Classes DSFR ajoutées à celles du thème classic
  className: 'calendar-app__calendar',
  headerToolbarClass: 'calendar-app__toolbar',
  toolbarTitleClass: 'calendar-app__toolbar-title',
  buttonClass: getButtonClass,
  buttonGroupClass: getButtonGroupClass,
  dayHeaderInnerClass: 'calendar-app__day-header',
  dayCellClass: getDayCellClass,
  dayCellTopInnerClass: getDayNumberClass,
  inlineWeekNumberClass: 'fr-badge calendar-app__week-badge calendar-app__week-badge--inline',
  listDayClass: getListDayClass,
  listDayHeaderClass: 'calendar-app__list-day-header',
  listDayHeaderInnerClass: getListDayHeaderInnerClass,
  listDayBodyClass: 'calendar-app__list-day-body',
  noEventsClass: 'calendar-app__no-events',
  eventInnerClass: 'calendar-app__event-inner',
  buttons: {
    prev: {
      class: 'calendar-app__nav-button',
    },
    next: {
      class: 'calendar-app__nav-button',
    },
  },
})
</script>

<style>
  /* Variables du thème classic remplacées par les tokens DSFR */
  .calendar-app {
    --fc-classic-border: var(--border-default-grey);
    --fc-classic-background: var(--background-default-grey);
    --fc-classic-foreground: var(--text-default-grey);
    --fc-classic-today: var(--background-alt-blue-france);
  }

  /* Préfixe .calendar-app pour l'emporter sur les classes utilitaires du thème classic */

  /* Barre d'outils collée à la grille */
  .calendar-app .calendar-app__calendar {
    gap: 0;
  }

  .calendar-app .calendar-app__toolbar {
    gap: 1.5rem;
    padding: 1rem;
    background-color: var(--background-alt-grey);
    border: 1px solid var(--border-default-grey);
    border-bottom: 0;
  }

  .calendar-app .calendar-app__toolbar-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 2rem;
    color: var(--text-title-grey);
  }

  /* Majuscule sur la première lettre seulement : "novembre 2026" devient "Novembre 2026" */
  .calendar-app .calendar-app__toolbar-title::first-letter {
    text-transform: uppercase;
  }

  /* Boutons précédent / suivant */
  .calendar-app .calendar-app__nav-button {
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    padding: 0;
    color: var(--text-action-high-blue-france);
    background-color: var(--background-alt-grey);
    border: 1px solid var(--border-default-grey);
  }

  .calendar-app .calendar-app__nav-button:hover {
    background-color: var(--background-alt-grey-hover);
  }

  .calendar-app .calendar-app__nav-button:active {
    background-color: var(--background-alt-grey-active);
  }

  /* Choix de la vue : reprise du contrôle segmenté DSFR (fr-segmented) */
  .calendar-app .calendar-app__view-buttons {
    background-color: var(--background-default-grey);
    border-radius: 0.25rem;
    box-shadow: inset 0 0 0 1px var(--border-default-grey);
  }

  .calendar-app .calendar-app__view-button {
    min-height: 2.5rem;
    padding: 0.5rem 1rem;
    font-size: 1rem;
    font-weight: 500;
    line-height: 1.5rem;
    color: var(--text-default-grey);
    background-color: transparent;
    border: 0;
    border-radius: 0.25rem;
  }

  .calendar-app .calendar-app__view-button:hover {
    background-color: var(--background-default-grey-hover);
  }

  .calendar-app .calendar-app__view-button:active {
    background-color: var(--background-default-grey-active);
  }

  .calendar-app .calendar-app__view-button--selected,
  .calendar-app .calendar-app__view-button--selected:hover {
    color: var(--text-active-blue-france);
    background-color: var(--background-default-grey);
    box-shadow: inset 0 0 0 1px var(--border-active-blue-france);
  }

  /* En-têtes des jours
  Le thème empile le contenu de l'en-tête en colonne : on le remet en ligne (jour + pastille en vue semaine) */
  .calendar-app .calendar-app__day-header {
    margin-block: 0.375rem;
    font-size: 1rem;
    font-weight: 500;
    line-height: 1.5rem;
    color: var(--text-title-grey);
    text-transform: capitalize;
    flex-direction: row;
    align-items: center;
    gap: 0.25rem;
  }

  /* Jours des mois voisins grisés */
  .calendar-app .calendar-app__day--other {
    background-color: var(--background-alt-grey);
  }

  /* Numéros des jours */
  .calendar-app .calendar-app__day-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.75rem;
    height: 1.75rem;
    margin: 0.5rem 0.5rem 0;
    font-size: 1rem;
    font-weight: 500;
    line-height: 1.5rem;
    color: var(--text-action-high-blue-france);
  }

  .calendar-app .calendar-app__day--other .calendar-app__day-number {
    font-weight: 400;
    color: var(--text-mention-grey);
  }

  /* Pastille du jour courant (dans la grille, elle reprend les dimensions de .calendar-app__day-number) */
  .calendar-app .calendar-app__today-pill {
    background-color: var(--background-open-blue-france-active);
    border-radius: 50%;
  }

  /* Pastille dans l'en-tête de la vue semaine, plus petite */
  .calendar-app .calendar-app__day-header .calendar-app__today-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.5rem;
    height: 1.5rem;
  }

  /* Badge du numéro de semaine (style du badge DSFR --new, sans icône) */
  /* Dimensions de .fr-badge répétées : le thème classic les écrase sur le badge de la vue mois */
  .calendar-app .calendar-app__week-badge {
    padding: 0 0.5rem;
    font-size: 0.875rem;
    font-weight: 700;
    line-height: 1.5rem;
    border-radius: 0.25rem;
    color: var(--text-action-high-yellow-moutarde);
    background-color: var(--yellow-tournesol-950-100);
  }

  .calendar-app .calendar-app__week-badge--inline {
    top: 0.75rem;
    left: 0.75rem;
  }

  /* Vue liste : en-tête du jour à gauche, événements à droite */
  .calendar-app .calendar-app__list-day {
    display: flex;
    align-items: flex-start;
    padding-block: 1rem;
  }

  .calendar-app .calendar-app__list-day--today {
    background-color: var(--background-alt-blue-france);
  }

  .calendar-app .calendar-app__list-day-header {
    flex-direction: column;
    flex-shrink: 0;
    align-items: flex-start;
    justify-content: flex-start;
    width: 12rem;
    margin: 0;
    padding-inline: 0.75rem;
    background: none;
    border-bottom: 0;
  }

  .calendar-app .calendar-app__list-day--today .calendar-app__list-day-header {
    border-left: 2px solid var(--border-default-blue-france);
  }

  .calendar-app .calendar-app__list-day-weekday,
  .calendar-app .calendar-app__list-day-date {
    padding: 0;
    font-size: 1rem;
    line-height: 1.5rem;
    color: var(--text-title-grey);
  }

  .calendar-app .calendar-app__list-day-weekday {
    font-weight: 700;
    text-transform: capitalize;
  }

  .calendar-app .calendar-app__list-day-date {
    font-weight: 400;
  }

  .calendar-app .calendar-app__list-day-body {
    flex: 1;
    min-width: 0;
  }

  /* Textes des événements (12px dans le thème classic) */
  .calendar-app .calendar-app__event-inner {
    font-size: 0.875rem;
    line-height: 1.5rem;
  }

  /* Message "Aucun évènement à afficher" (semaine entièrement vide) */
  .calendar-app .calendar-app__no-events {
    font-style: italic;
    color: var(--text-mention-grey);
    background-color: var(--background-default-grey);
  }
</style>
