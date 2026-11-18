<template>
  <dialog
    ref="popover"
    class="calendar-event-popover"
    :aria-labelledby="titleId"
  >
    <div class="calendar-event-popover__header">
      <h2
        :id="titleId"
        ref="title"
        tabindex="-1"
        class="calendar-event-popover__title"
        :class="`calendar-event-popover__title--${eventProps.type}`"
      >
        {{ event.title }}
      </h2>
      <button
        type="button"
        class="fr-btn fr-btn--tertiary-no-outline fr-btn--sm fr-icon-close-line"
        title="Fermer"
        @click="emit('close')"
      >
        Fermer
      </button>
    </div>

    <div class="calendar-event-popover__body">
      <ul class="calendar-event-popover__infos">
        <li>
          <span class="fr-icon-calendar-line fr-icon--sm" aria-hidden="true"></span>
          {{ dateLabel }}
        </li>
        <li>
          <span class="fr-icon-time-line fr-icon--sm" aria-hidden="true"></span>
          {{ timeLabel }}
        </li>
        <li v-if="eventProps.occupant">
          <span class="fr-icon-home-4-line fr-icon--sm" aria-hidden="true"></span>
          <span class="fr-sr-only">Occupant :</span>
          {{ eventProps.occupant }}
        </li>
        <li v-if="eventProps.operator">
          <span class="fr-icon-briefcase-line fr-icon--sm" aria-hidden="true"></span>
          <span class="fr-sr-only">Opérateur :</span>
          {{ eventProps.operator }}
        </li>
      </ul>

      <p class="calendar-event-popover__link">
        <a
          v-if="eventProps.type === 'club'"
          :href="eventProps.link"
          class="fr-link fr-icon-arrow-right-line fr-link--icon-right"
          target="_blank"
          rel="noopener"
          title="S'inscrire - ouvre une nouvelle fenêtre"
        >
          S'inscrire
        </a>
        <a
          v-else
          :href="eventProps.link"
          class="fr-link fr-icon-arrow-right-line fr-link--icon-right"
        >
          Voir le dossier
        </a>
      </p>
    </div>
  </dialog>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, useId, useTemplateRef } from 'vue'
import type { EventApi } from '@fullcalendar/vue3'
import type { CalendarEventProps } from '../types'

interface Props {
  event: EventApi
  // Élément cliqué, auquel la popup est ancrée
  anchor: HTMLElement
}
const props: Props = defineProps<Props>()

const emit = defineEmits<{
  close: []
}>()

// Écart entre la popup et l'élément cliqué, et marge minimale avec les bords de la fenêtre (px)
const OFFSET = 8
const VIEWPORT_MARGIN = 8

const dateFormatter = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })

const popover = useTemplateRef<HTMLDialogElement>('popover')
const title = useTemplateRef<HTMLElement>('title')
const titleId = useId()

const eventProps = computed<CalendarEventProps>(() => props.event.extendedProps as CalendarEventProps)

const dateLabel = computed<string>(() => {
  if (!props.event.start) {
    return ''
  }
  const label = dateFormatter.format(props.event.start)
  return label.charAt(0).toUpperCase() + label.slice(1)
})

const timeLabel = computed<string>(() => {
  const start = props.event.start
  if (!start) {
    return ''
  }
  return `${start.getHours()}:${String(start.getMinutes()).padStart(2, '0')}`
})

/**
 * Place la popup sous l'élément cliqué, ou au-dessus s'il n'y a pas la place,
 * sans qu'elle déborde de la fenêtre
 */
const updatePosition = (): void => {
  const dialog = popover.value
  if (!dialog) {
    return
  }

  const anchorRect = props.anchor.getBoundingClientRect()
  const { width, height } = dialog.getBoundingClientRect()

  const fitsBelow = anchorRect.bottom + OFFSET + height <= window.innerHeight - VIEWPORT_MARGIN
  const top = fitsBelow
    ? anchorRect.bottom + OFFSET
    : Math.max(VIEWPORT_MARGIN, anchorRect.top - OFFSET - height)
  const left = Math.min(
    Math.max(VIEWPORT_MARGIN, anchorRect.left),
    window.innerWidth - width - VIEWPORT_MARGIN
  )

  dialog.style.top = `${top}px`
  dialog.style.left = `${left}px`
}

/**
 * Fermeture au clic en dehors de la popup, y compris sur l'élément d'ancrage
 * (un clic sur l'événement la rouvre aussitôt, un clic sur "+ x autres" ouvre la liste à la place)
 */
const onDocumentMouseDown = (event: MouseEvent): void => {
  if (!popover.value?.contains(event.target as Node)) {
    emit('close')
  }
}

const onDocumentKeyDown = (event: KeyboardEvent): void => {
  if (event.key === 'Escape') {
    emit('close')
  }
}

onMounted(() => {
  if (!popover.value) {
    return
  }

  popover.value.show()
  updatePosition()
  // Focus sur le titre : annoncé par les lecteurs d'écran, indicateur de focus visible
  title.value?.focus()

  document.addEventListener('mousedown', onDocumentMouseDown)
  document.addEventListener('keydown', onDocumentKeyDown)
  // Suit l'élément cliqué lors d'un défilement (de la page ou d'un conteneur) ou d'un redimensionnement
  window.addEventListener('scroll', updatePosition, { capture: true, passive: true })
  window.addEventListener('resize', updatePosition, { passive: true })
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDocumentMouseDown)
  document.removeEventListener('keydown', onDocumentKeyDown)
  window.removeEventListener('scroll', updatePosition, { capture: true })
  window.removeEventListener('resize', updatePosition)

  // Rend le focus à l'élément d'origine s'il est toujours affiché
  if (props.anchor.isConnected) {
    props.anchor.focus({ preventScroll: true })
  }
})
</script>

<style>
  .calendar-event-popover {
    /* Styles par défaut de <dialog> neutralisés : position calculée dans updatePosition() */
    position: fixed;
    inset: auto;
    margin: 0;
    padding: 0;
    color: inherit;
    z-index: 1000;
    width: max-content;
    min-width: 16rem;
    max-width: 22rem;
    background-color: var(--background-default-grey);
    border: 1px solid var(--border-default-grey);
    box-shadow: var(--overlap-shadow);
  }

  .calendar-event-popover__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.5rem 0.5rem 0.5rem 1rem;
    background-color: var(--background-alt-grey);
    border-bottom: 1px solid var(--border-default-grey);
  }

  /* Titre précédé du trait de couleur du type d'événement */
  .calendar-event-popover__title {
    margin: 0;
    padding-left: 0.5rem;
    font-size: 1.125rem;
    line-height: 1.75rem;
    border-left: 0.25rem solid;
  }

  .calendar-event-popover__title--club {
    border-left-color: var(--calendar-event-club);
  }

  .calendar-event-popover__title--visite {
    border-left-color: var(--calendar-event-visite);
  }

  .calendar-event-popover__body {
    padding: 1rem;
  }

  .calendar-event-popover__infos {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .calendar-event-popover__infos li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0;
    margin-bottom: 0.5rem;
  }

  .calendar-event-popover__link {
    margin: 0.5rem 0 0;
    text-align: right;
  }
</style>
