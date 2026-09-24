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
          >Carte</button>
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
          >Liste</button>
      </div>
    </div>
    <div class="fr-col-12 fr-col-lg-6 fr-col-xl-4 fr-mb-2v fr-mb-md-0 fr-text--right">
        <nav class="fr-translate menu-actions-signalement align-right fr-nav">
            <div class="fr-nav__item">
                <button
                  type="button"
                  aria-controls="export-menu"
                  aria-expanded="false"
                  class="fr-btn fr-btn--secondary fr-btn--icon-right fr-icon-arrow-down-s-line"
                  :class="{'fr-label--disabled': !canExport}"
                  >Exporter les résultats</button>
                <div class="fr-collapse fr-translate__menu fr-menu" id="export-menu">
                    <ul class="fr-menu__list">
                        <li>
                            <button
                              :class="['fr-nav__link', { 'fr-label--disabled': !canExport || isExporting }]"
                              @click="onExport('xlsx')"
                              >Télécharger au format Excel (.xlsx)</button>
                        </li>
                        <li>
                            <button
                              :class="['fr-nav__link', { 'fr-label--disabled': !canExport || isExporting }]"
                              @click="onExport('csv')"
                              >Télécharger au format .csv</button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { store, useAddressesHistoryStore } from '../composables/useAddressesHistoryStore'
import { useAddressesHistoryApi } from '../api'

// Émissions
const emit = defineEmits<{
  viewModeChange: [viewMode: string]
}>()

// State
const sharedState = store.state
const { canExport } = useAddressesHistoryStore()
const isExporting = ref(false)

const api = useAddressesHistoryApi(store.props)

/**
 * Quand le mode d'affichage change (carte/liste)
 */
const onViewModeChange = (viewMode: string): void => {
  emit('viewModeChange', viewMode)
}

/**
 * Clic sur les boutons d'export avec la bonne extension
 */
const onExport = async (format: 'csv' | 'xlsx'): Promise<void> => {
  if (!canExport.value || isExporting.value) {
    return
  }

  isExporting.value = true
  try {
    await api.downloadList(format)
  } catch (error) {
    console.error('Error exporting addresses:', error)
  } finally {
    isExporting.value = false
  }
}
</script>

<style>
</style>

