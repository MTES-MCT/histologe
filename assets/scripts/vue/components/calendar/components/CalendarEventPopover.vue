<template>
  <div
    ref="popover"
    class="calendar-event-popover"
    role="dialog"
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
        <li v-if="timeLabel">
          <span class="fr-icon-time-line fr-icon--sm" aria-hidden="true"></span>
          {{ timeLabel }}
        </li>
        <li v-if="eventProps.occupant">
          <span class="fr-icon-home-4-line fr-icon--sm" aria-hidden="true"></span>
          <span class="fr-sr-only">Occupant :</span>
          {{ eventProps.occupant }}
        </li>
        <li v-if="operatorLabel">
          <span class="fr-icon-briefcase-line fr-icon--sm" aria-hidden="true"></span>
          <span class="fr-sr-only">Opérateur :</span>
          {{ operatorLabel }}
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
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, useId, useTemplateRef } from 'vue'
import { createPopper } from '@popperjs/core'
import type { Instance } from '@popperjs/core'
import type { EventApi } from '@fullcalendar/vue3'
import type { CalendarEventProps } from '../types'

// Props
interface Props {
  event: EventApi
  // Élément cliqué, auquel la popup est ancrée
  anchor: HTMLElement
}
const props: Props = defineProps<Props>()

// Émissions
const emit = defineEmits<{
  close: []
}>()

const dateFormatter = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })

const popover = useTemplateRef<HTMLElement>('popover')
const title = useTemplateRef<HTMLElement>('title')
const titleId = useId()
let popperInstance: Instance | null = null

const eventProps = computed<CalendarEventProps>(() => props.event.extendedProps as CalendarEventProps)

// "Lundi 2 novembre"
const dateLabel = computed<string>(() => {
  if (!props.event.start) {
    return ''
  }
  const label = dateFormatter.format(props.event.start)
  return label.charAt(0).toUpperCase() + label.slice(1)
})

// "9:00" (pas d'heure pour un événement sur la journée entière)
const timeLabel = computed<string>(() => {
  const start = props.event.start
  if (!start || props.event.allDay) {
    return ''
  }
  return `${start.getHours()}:${String(start.getMinutes()).padStart(2, '0')}`
})

// Visite sans partenaire : opérateur externe
const operatorLabel = computed<string>(() => {
  if (eventProps.value.type !== 'visite') {
    return ''
  }
  return eventProps.value.operator || 'Opérateur externe'
})

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

  // Sous l'élément cliqué, ou au-dessus s'il n'y a pas la place
  popperInstance = createPopper(props.anchor, popover.value, {
    placement: 'bottom-start',
    strategy: 'fixed',
    modifiers: [{ name: 'offset', options: { offset: [0, 8] } }],
  })
  // Focus sur le titre : annoncé par les lecteurs d'écran, indicateur de focus visible
  title.value?.focus()

  document.addEventListener('mousedown', onDocumentMouseDown)
  document.addEventListener('keydown', onDocumentKeyDown)
})

onBeforeUnmount(() => {
  popperInstance?.destroy()
  document.removeEventListener('mousedown', onDocumentMouseDown)
  document.removeEventListener('keydown', onDocumentKeyDown)

  // Rend le focus à l'élément d'origine s'il est toujours affiché
  if (props.anchor.isConnected) {
    props.anchor.focus({ preventScroll: true })
  }
})
</script>

<style>
  .calendar-event-popover {
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
