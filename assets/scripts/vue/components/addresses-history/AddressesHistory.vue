<template>
  <div id="histo-app-addresses-history-view">
    <AddressesHistoryHeader @view-mode-change="onViewModeChange" />
    <section v-if="sharedState.loadingList" class="loading fr-m-10w fr-text--center">
      <h2 class="fr-text--light" v-if="!sharedState.hasErrorLoading">Chargement de la liste...</h2>
      <h2 class="fr-text--light" v-if="sharedState.hasErrorLoading">Erreur lors du chargement de la liste.</h2>
      <p v-if="sharedState.hasErrorLoading">Veuillez recharger la page ou nous prévenir via le formulaire de contact.</p>
    </section>
    <AddressesHistoryMap v-else-if="sharedState.viewMode === 'map'" />
    <AddressesHistoryList
      v-else-if="sharedState.viewMode === 'list'"
      @change="onFiltersChange"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { store } from './store'
import { requests } from './requests'
import { useAddressesHistoryFilters } from './composables/useAddressesHistoryFilters'

import AddressesHistoryHeader from './components/AddressesHistoryHeader.vue'
import AddressesHistoryMap from './components/AddressesHistoryMap.vue'
import AddressesHistoryList from './components/AddressesHistoryList.vue'

// State
const sharedState = store.state
const sharedProps = store.props

// Composable
const filtersComposable = useAddressesHistoryFilters()

/**
 * Gère le changement des filtres
 * Recharge les données avec les nouveaux filtres
 */
const onFiltersChange = (): void => {
  filtersComposable.reloadAddresses(filtersComposable.handleAddressesResponse)
}

/**
 * Gère le changement de mode d'affichage (carte/liste)
 */
const onViewModeChange = (viewMode: string): void => {
  sharedState.viewMode = viewMode
}

/**
 * Initialisation
 */
const init = (): void => {
  const initElements = document.querySelector('#app-addresses-history-view') as HTMLElement | null
  if (!initElements) {
    sharedState.hasErrorLoading = true
    return
  }

  sharedState.hasErrorLoading = false
  sharedProps.ajaxurlAddresses = initElements.dataset.ajaxurl || ''
  sharedProps.baseAjaxUrlAddresses = initElements.dataset.ajaxurl || ''
  sharedProps.ajaxurlSettings = initElements.dataset.ajaxurlSettings || ''
  sharedProps.ajaxurlExportCsv = initElements.dataset.ajaxurlExportCsv || ''
  sharedProps.platformName = initElements.dataset.platformName || ''

  // Charge les settings et les données en parallèle
  requests.getSettings(filtersComposable.handleSettingsResponse)
  filtersComposable.reloadAddresses(filtersComposable.handleAddressesResponse)
}

onMounted(() => {
  init()
})
</script>

<style>
#histo-app-signalement-view .fr-container--fluid {
  overflow: visible;
}
</style>

