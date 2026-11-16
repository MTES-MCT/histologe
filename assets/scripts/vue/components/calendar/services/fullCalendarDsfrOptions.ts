import type {
  ButtonGroupInfo,
  ButtonInfo,
  CalendarOptions,
  DayCellInfo,
  DayHeaderInfo,
  EventInput,
  InlineWeekNumberInfo,
  ListDayHeaderInnerInfo,
  ListDayInfo,
} from '@fullcalendar/vue3'

import '@fullcalendar/vue3/skeleton.css'
import '@fullcalendar/vue3/themes/classic/theme.css'
import '@fullcalendar/vue3/themes/classic/palette.css'
import '../styles/fullCalendarDsfr.css'

// Bornes d'une période à formater (dates "marqueurs" FullCalendar, codées en UTC)
interface TitleRange {
  start: { marker: Date }
  end?: { marker: Date } | null
}

// Couleurs des événements selon leur type (variables définies dans styles/fullCalendarDsfr.css)
const EVENT_COLORS: Record<string, { color: string; contrastColor: string }> = {
  club: { color: 'var(--calendar-event-club)', contrastColor: 'var(--calendar-event-club-contrast)' },
  visite: { color: 'var(--calendar-event-visite)', contrastColor: 'var(--calendar-event-visite-contrast)' },
}

const dayFormatter = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', timeZone: 'UTC' })
const dayMonthFormatter = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', timeZone: 'UTC' })
const fullDateFormatter = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' })

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
 * Couleur de l'événement selon son type (extendedProps.type : "club" ou "visite")
 */
const applyEventColors = (event: EventInput): EventInput => {
  const colors = EVENT_COLORS[event.extendedProps?.type]
  return colors ? { ...event, ...colors } : event
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

// Options d'affichage DSFR, à combiner avec les options du calendrier (plugins, barre d'outils, événements…)
export const dsfrOptions: CalendarOptions = {
  views: {
    // Vues mois et semaine : heure alignée à droite du titre
    dayGrid: {
      eventTitleClass: 'calendar-app__event-title',
      eventTimeClass: 'calendar-app__event-time',
    },
    // Numéro de semaine dans la première cellule de chaque ligne (le lundi)
    dayGridMonth: {
      weekNumbers: true,
    },
    // En-têtes avec la date : "Lundi 2"
    dayGridWeek: {
      dayHeaderFormat: { weekday: 'long', day: 'numeric' },
      dayHeaderContent: getWeekDayHeaderContent,
    },
    // Vue liste : marqueur de couleur placé avant l'heure
    list: {
      listItemEventBeforeClass: 'calendar-app__event-marker calendar-app__event-marker--before-time',
    },
    // Colonne de gauche : "Lundi" puis "2 novembre 2026"
    listWeek: {
      titleFormat: formatListTitle,
      listDayFormat: { weekday: 'long' },
      listDayAltFormat: { day: 'numeric', month: 'long', year: 'numeric' },
    },
  },
  // Noms des jours en entier dans l'en-tête ("lundi")
  dayHeaderFormat: { weekday: 'long' },
  eventDataTransform: applyEventColors,
  inlineWeekNumberContent: getInlineWeekNumberContent,
  dayCellTopContent: getDayCellTopContent,
  // Classes DSFR ajoutées à celles du thème classic (styles dans styles/fullCalendarDsfr.css)
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
  // Marqueur de couleur à gauche des événements avec heure (rond dans le thème classic)
  listItemEventBeforeClass: 'calendar-app__event-marker',
  buttons: {
    prev: {
      class: 'calendar-app__nav-button',
    },
    next: {
      class: 'calendar-app__nav-button',
    },
  },
}
