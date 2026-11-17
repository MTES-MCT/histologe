<template>
  <div class="calendar-app">
    <div class="calendar-app__header">
      <h1 class="fr-mb-0">Calendrier</h1>
      <CalendarFilters
        v-model="sharedState.filters"
        :territories="sharedState.territories"
        :operators="sharedState.operators"
      />
    </div>
    <section v-if="sharedState.hasErrorLoading" class="calendar-app__message fr-m-10w" role="alert">
      <h2 class="fr-text--light">Erreur lors du chargement du calendrier.</h2>
      <p>Veuillez recharger la page ou nous prévenir via le formulaire de contact.</p>
    </section>
    <template v-else>
      <!-- Toujours présent pour que les lecteurs d'écran annoncent le changement de contenu -->
      <p class="calendar-app__message calendar-app__status" role="status">
        <template v-if="sharedState.loadingInit">Chargement des événements...</template>
      </p>
      <CalendarView />
    </template>
  </div>
</template>

<script setup lang="ts">
import { store } from './composables/useCalendarStore'
import CalendarFilters from './components/CalendarFilters.vue'
import CalendarView from './components/CalendarView.vue'
import type { CalendarFilterOption } from './types'

// State
const sharedState = store.state
const sharedProps = store.props

/**
 * Lit une liste d'options JSON passée en data-attribute
 */
const parseOptions = (json: string | undefined): CalendarFilterOption[] => {
  return json ? JSON.parse(json) : []
}

/**
 * Initialisation à partir des data-attributes de #calendar-app
 * Les événements sont chargés par le calendrier lui-même
 */
const init = (): void => {
  const initElement = document.querySelector('#calendar-app') as HTMLElement | null
  if (!initElement) {
    sharedState.hasErrorLoading = true
    return
  }

  sharedProps.ajaxurl = initElement.dataset.ajaxurl || ''
  sharedState.territories = parseOptions(initElement.dataset.territories)
  sharedState.operators = parseOptions(initElement.dataset.operators)
  sharedState.filters.uniquementMesDossiers = initElement.dataset.uniquementMesDossiers === '1'
}

// Appelé dès le setup (et non dans onMounted) : le calendrier charge ses événements
// à son montage, avant le onMounted de l'app, et ajaxurl doit déjà être connu
init()
</script>

<style>
  /* Titre de la page et filtres sur la même ligne */
  .calendar-app__header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
  }

  .calendar-app__message {
    text-align: center;
  }

  .calendar-app__status:empty {
    margin: 0;
  }
</style>
