import type {
  ButtonGroupInfo,
  ButtonInfo,
  CalendarOptions,
  DayCellInfo,
  DayHeaderInfo,
  EventDisplayInfo,
  EventInput,
  InlineWeekNumberInfo,
  ListDayHeaderInnerInfo,
  ListDayInfo,
} from '@fullcalendar/vue3'

import type { CalendarEventProps } from '../types'

import '@fullcalendar/vue3/skeleton.css'
import '@fullcalendar/vue3/themes/classic/theme.css'
import '@fullcalendar/vue3/themes/classic/palette.css'
import '../styles/fullCalendarDsfr.css'

interface TitleRange {
  start: { marker: Date }
  end?: { marker: Date } | null
}

// Couleurs des événements selon leur type (variables définies dans styles/fullCalendarDsfr.css)
const EVENT_COLORS: Record<CalendarEventProps['type'], string> = {
  club: 'var(--calendar-event-club)',
  visite: 'var(--calendar-event-visite)',
  visite_controle: 'var(--calendar-event-visite-controle)',
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
 * Libellé court du numéro de semaine dans la vue mois : "S45", lu "Semaine 45"
 */
const getInlineWeekNumberContent = (info: InlineWeekNumberInfo): { html: string } => {
  return {
    html: `<span aria-hidden="true">S${info.num}</span><span class="fr-sr-only">Semaine ${info.num}</span>`,
  }
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
 * Couleur de l'événement selon son type (extendedProps.type : "club" ou "visite" ou "visite-controle")
 */
const applyEventColors = (event: EventInput): EventInput => {
  const type = (event.extendedProps as CalendarEventProps | undefined)?.type
  return type ? { ...event, color: EVENT_COLORS[type] } : event
}

/**
 * En-tête de jour : dans la popup "+ x autres", jour de la semaine puis date complète
 */
const getDayHeaderContent = (info: DayHeaderInfo): { html: string } | true => {
  if (!info.inPopover) {
    return true
  }

  const calendar = info.view.calendar
  const weekday = calendar.formatDate(info.date, { weekday: 'long' })
  const date = calendar.formatDate(info.date, { day: 'numeric', month: 'long', year: 'numeric' })
  return {
    html: `<span class="calendar-app__popover-weekday">${weekday}</span><span class="calendar-app__popover-date">${date}</span>`,
  }
}

/**
 * Classes de l'en-tête de jour (en-tête de la popup "+ x autres" à part)
 */
const getDayHeaderClass = (info: DayHeaderInfo): string => {
  return info.inPopover ? 'calendar-app__popover-header' : ''
}

const getDayHeaderInnerClass = (info: DayHeaderInfo): string => {
  return info.inPopover ? 'calendar-app__popover-header-inner' : 'calendar-app__day-header'
}

/**
 * Lien de la vue mois quand une journée a trop d'événements : "+ 1 autre", "+ 3 autres"
 */
const getMoreLinkText = (num: number): string => {
  return num > 1 ? `+ ${num} autres` : `+ ${num} autre`
}

/**
 * Crée un élément HTML (texte inséré tel quel, sans interprétation HTML)
 */
const createElement = (tag: string, className: string, text: string = ''): HTMLElement => {
  const element = document.createElement(tag)
  element.className = className
  element.textContent = text
  return element
}

/**
 * Événement de la vue semaine : titre sur plusieurs lignes,
 * suivi du nom de l'occupant entre parenthèses pour une visite, puis heure à droite
 */
const getWeekEventContent = (info: EventDisplayInfo): { domNodes: Node[] } => {
  const eventProps = info.event.extendedProps as CalendarEventProps

  const title = createElement('span', 'calendar-app__event-title calendar-app__event-title--multiline')
  title.append(createElement('span', '', info.event.title))
  if (eventProps.occupant) {
    title.append(createElement('span', '', `(${eventProps.occupant})`))
  }

  const nodes: Node[] = [title]
  if (info.timeText) {
    nodes.push(createElement('span', 'calendar-app__event-time', info.timeText))
  }
  return { domNodes: nodes }
}

/**
 * Événement de la vue liste : heure, titre et occupant en gras, opérateur,
 * puis lien vers le dossier (visite) ou l'inscription (club) à droite
 */
const getListEventContent = (info: EventDisplayInfo): { domNodes: Node[] } => {
  const eventProps = info.event.extendedProps as CalendarEventProps

  const title = createElement('p', 'calendar-app__list-event-title')
  const occupant = eventProps.occupant ? ` (${eventProps.occupant})` : ''
  title.append(createElement('strong', '', `${info.event.title}${occupant}`))
  if (eventProps.operator) {
    title.append(` ${eventProps.operator}`)
  }

  // Intitulé complété par le titre de l'événement pour les lecteurs d'écran (liens identiques sur la page)
  const linkText = eventProps.type === 'club' ? "S'inscrire" : 'Voir le dossier'
  const link = createElement(
    'a',
    'fr-link fr-icon-arrow-right-line fr-link--icon-right calendar-app__event-link',
    linkText
  ) as HTMLAnchorElement
  link.append(createElement('span', 'fr-sr-only', ` ${info.event.title}`))
  link.href = eventProps.link
  if (eventProps.type === 'club') {
    link.target = '_blank'
    link.rel = 'noopener'
    link.title = `${linkText} ${info.event.title} - ouvre une nouvelle fenêtre`
  }

  return {
    domNodes: [createElement('span', 'calendar-app__list-event-time', info.timeText), title, link],
  }
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
      // Au-delà, lien "+ x autres" qui ouvre une popup avec tous les événements du jour
      dayMaxEvents: 3,
    },
    // En-têtes avec la date : "Lundi 2"
    dayGridWeek: {
      dayHeaderFormat: { weekday: 'long', day: 'numeric' },
      dayHeaderContent: getWeekDayHeaderContent,
      eventContent: getWeekEventContent,
      // Trait de couleur sur toute la hauteur de l'événement (titre sur plusieurs lignes)
      listItemEventBeforeClass: 'calendar-app__event-marker calendar-app__event-marker--full-height',
      listItemEventClass: 'calendar-app__week-event',
    },
    // Vue liste : marqueur de couleur placé avant l'heure, contenu sur toute la largeur
    list: {
      listItemEventBeforeClass: 'calendar-app__event-marker calendar-app__event-marker--before-time',
      listItemEventClass: 'calendar-app__list-event',
      listItemEventInnerClass: 'calendar-app__list-event-inner',
      eventContent: getListEventContent,
      // Pas de popup en vue liste : la ligne n'est pas un bouton, seul le lien est interactif
      eventInteractive: false,
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
  dayHeaderContent: getDayHeaderContent,
  moreLinkText: getMoreLinkText,
  eventDataTransform: applyEventColors,
  inlineWeekNumberContent: getInlineWeekNumberContent,
  dayCellTopContent: getDayCellTopContent,
  // Classes DSFR ajoutées à celles du thème classic (styles dans styles/fullCalendarDsfr.css)
  className: 'calendar-app__calendar',
  headerToolbarClass: 'calendar-app__toolbar',
  toolbarTitleClass: 'calendar-app__toolbar-title',
  buttonClass: getButtonClass,
  buttonGroupClass: getButtonGroupClass,
  dayHeaderClass: getDayHeaderClass,
  dayHeaderInnerClass: getDayHeaderInnerClass,
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
  moreLinkClass: 'calendar-app__more-link',
  // Texte du lien "+ x autres" (12px dans le thème classic)
  moreLinkInnerClass: 'calendar-app__more-link-inner',
  // Popup "+ x autres" (insérée dans <body>, hors de .calendar-app)
  popoverClass: 'calendar-app__popover',
  popoverCloseClass: 'calendar-app__popover-close',
  popoverCloseContent: { html: '<span class="fr-icon-close-line fr-icon--sm" aria-hidden="true"></span>' },
  buttons: {
    prev: {
      class: 'calendar-app__nav-button',
    },
    next: {
      class: 'calendar-app__nav-button',
    },
  },
}
