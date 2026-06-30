<template>
  <section class="fr-grid-row fr-mb-1w fr-container-sml">
    <div class="fr-col-12 fr-col-lg-6 fr-col-xl-8 fr-my-2v fr-my-md-0 fr-grid-row fr-grid-row--middle">
      <div class="fr-mr-4v">Affichage :</div>
      <div class="fr-mr-2v">
        <button
          :class="[
            'fr-btn',
            'fr-btn--icon-left',
            'fr-icon-road-map-line',
            sharedState.viewMode === 'map' ? '' : 'fr-btn--tertiary'
          ]"
          type="button"
          @click="onViewModeChange('map')"
        >
          Carte
        </button>
      </div>
      <div>
        <button
          :class="[
            'fr-btn',
            'fr-btn--icon-left',
            'fr-icon-list-unordered',
            sharedState.viewMode === 'list' ? '' : 'fr-btn--tertiary'
          ]"
          type="button"
          @click="onViewModeChange('list')"
        >
          Liste
        </button>
      </div>
    </div>
    <div class="fr-col-12 fr-col-lg-6 fr-col-xl-4 fr-mb-2v fr-mb-md-0 fr-text--right">
      <a
        :href="canExport ? `${sharedProps.ajaxurlExportCsv}` : undefined"
        :class="[
          'fr-btn',
          'fr-btn--secondary',
          'fr-btn--icon-left',
          'fr-icon-download-fill',
          'fr-btn--block',
          'fr-btn--md-inline',
          { 'fr-label--disabled': !canExport }
        ]"
      >
        Exporter les résultats
      </a>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { store } from '../store'

// Émissions
const emit = defineEmits<{
  viewModeChange: [viewMode: string]
}>()

// State
const sharedState = store.state
const sharedProps = store.props

// Computed
const canExport = computed(() => {
  return false
  /*
  return Object.entries(sharedState.input.filters).some(
    ([key, value]) => key !== 'isImported' && (value !== null && value !== undefined && !(Array.isArray(value) && value.length === 0))
  ) && total.value > 0
   */
})

/**
 * Quand le mode d'affichage change (carte/liste)
 */
const onViewModeChange = (viewMode: string): void => {
  emit('viewModeChange', viewMode)
}
</script>

<style>
</style>

