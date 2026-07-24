<template>
  <div id="histo-app-addresses-history-view">
    <AddressesHistoryHeader @view-mode-change="onViewModeChange" />
    <hr class="fr-mt-4w">
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
import { store } from './composables/useAddressesHistoryStore'
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
const onFiltersChange = async (): Promise<void> => {
  await filtersComposable.reloadAddresses()
}

/**
 * Gère le changement de mode d'affichage (carte/liste)
 */
const onViewModeChange = async (viewMode: string): Promise<void> => {
  // Change le mode de vue (sera inclus dans l'URL lors du reload)
  sharedState.viewMode = viewMode as any

  // Recharge les adresses sans filtres pour la carte, avec filtres pour la liste
  if (viewMode === 'map') {
    // Pour la carte, on charge toutes les adresses sans filtres
    const currentFilters = { ...sharedState.input.filters }
    // Sauvegarde temporaire des filtres
    const savedFilters = { ...currentFilters }
    // Réinitialise les filtres
    Object.keys(sharedState.input.filters).forEach(key => {
      if (Array.isArray((sharedState.input.filters as any)[key])) {
        (sharedState.input.filters as any)[key] = []
      } else {
        (sharedState.input.filters as any)[key] = undefined
      }
    })
    // Charge les adresses
    await filtersComposable.reloadAddresses()
    // Restaure les filtres
    sharedState.input.filters = savedFilters
  } else {
    // Pour la liste, on recharge avec les filtres actuels
    await filtersComposable.reloadAddresses()
  }
}

/**
 * Initialisation
 */
const init = async (): Promise<void> => {
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

  // Initialise les filtres depuis l'URL avant de charger les données
  filtersComposable.initFiltersFromUrl()

  // Charge les settings et les données en parallèle
  await filtersComposable.reloadSettings()

  // Charge les adresses : sans filtres si mode carte, avec filtres si mode liste
  if (sharedState.viewMode === 'map') {
    // Pour la carte, on charge toutes les adresses sans filtres
    const savedFilters = { ...sharedState.input.filters }
    Object.keys(sharedState.input.filters).forEach(key => {
      if (Array.isArray((sharedState.input.filters as any)[key])) {
        (sharedState.input.filters as any)[key] = []
      } else {
        (sharedState.input.filters as any)[key] = undefined
      }
    })
    await filtersComposable.reloadAddresses()
    sharedState.input.filters = savedFilters
  } else {
    await filtersComposable.reloadAddresses()
  }
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

