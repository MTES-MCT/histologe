<template>
  <div id="histo-app-stats" class="histo-app-stats fr-pt-4v">
    <HistoBOTabHeader>
      <template #title>Statistiques</template>
    </HistoBOTabHeader>
    <section v-if="sharedState.hasErrorLoading" class="loading fr-m-10w">
      <h2 class="fr-text--light">Erreur lors du chargement des statistiques.</h2>
      <p>Veuillez recharger la page ou nous prévenir via le formulaire de contact.</p>
    </section>
    <div v-else-if="sharedState.loadingInit" class="loading fr-m-10w">
      Initialisation des filtres...
    </div>
    <div v-else>
      <StatsGlobal @territory-change="onTerritoryChange" />
      <StatsFilters @change="onFiltersChange" @reset="onFiltersReset" />
      <div v-if="sharedState.loadingStats" class="loading fr-m-10w">
        Mise à jour des statistiques...
        <br><br><br><br><br><br><br><br><br><br><br><br>
      </div>
      <StatsDetails v-else />
    </div>
  </div>
</template>

<script setup lang="ts">
import { nextTick, onMounted } from 'vue'
import { store } from './composables/useStatsStore'
import { useStatsFilters } from './composables/useStatsFilters'
import HistoBOTabHeader from '../common/HistoBOTabHeader.vue'
import StatsGlobal from './components/StatsGlobal.vue'
import StatsFilters from './components/StatsFilters.vue'
import StatsDetails from './components/StatsDetails.vue'

// State
const sharedState = store.state
const sharedProps = store.props

// Composable
const filtersComposable = useStatsFilters()

/**
 * Recharge les statistiques puis fait défiler la page vers la zone concernée
 */
const refresh = async (reinit: boolean): Promise<void> => {
  const isLoaded = await filtersComposable.reloadStats(reinit)
  if (!isLoaded) {
    return
  }

  await nextTick()

  const el = document.getElementById(reinit ? 'app-stats' : 'histo-stats-filters')
  el?.scrollIntoView({ behavior: 'smooth' })
}

const onFiltersChange = (): void => {
  refresh(false)
}

const onFiltersReset = (): void => {
  filtersComposable.resetFilters()
  refresh(false)
}

const onTerritoryChange = (): void => {
  filtersComposable.clearTerritoryDependentFilters()
  refresh(true)
}

/**
 * Initialisation
 */
const init = async (): Promise<void> => {
  const initElements = document.querySelector('#app-stats') as HTMLElement | null
  if (!initElements) {
    sharedState.hasErrorLoading = true
    return
  }

  sharedProps.ajaxurl = initElements.dataset.ajaxurl || ''
  filtersComposable.initDates()
  await refresh(true)
}

onMounted(() => {
  init()
})
</script>

<style>
  .loading {
    font-size: 2rem;
  }
</style>
