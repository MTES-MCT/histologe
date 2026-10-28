<template>
  <div class="histo-app-front-stats fr-pt-5w">
    <div class="fr-container">
      <h1>{{ sharedProps.platformName }} en quelques chiffres</h1>
    </div>
    <section v-if="sharedState.hasErrorLoading" class="loading fr-m-10w">
      <h2 class="fr-text--light">Erreur lors du chargement des statistiques.</h2>
      <p>Veuillez recharger la page ou nous prévenir via le formulaire de contact.</p>
    </section>
    <div v-else-if="sharedState.loadingInit" class="loading fr-m-10w">
      Initialisation des statistiques...
    </div>
    <div v-else>
      <FrontStatsGlobal />

      <div v-if="sharedState.loadingStats" class="loading fr-m-10w">
        Mise à jour des statistiques...
      </div>
      <FrontStatsTerritory v-else @territory-change="reloadStats" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { store } from './composables/useFrontStatsStore'
import { useFrontStats } from './composables/useFrontStats'
import FrontStatsGlobal from './components/FrontStatsGlobal.vue'
import FrontStatsTerritory from './components/FrontStatsTerritory.vue'

// State
const sharedState = store.state
const sharedProps = store.props

// Composable
const { reloadStats } = useFrontStats()

/**
 * Initialisation
 */
const init = async (): Promise<void> => {
  const initElements = document.querySelector('#app-front-stats') as HTMLElement | null
  if (!initElements) {
    sharedState.hasErrorLoading = true
    return
  }

  sharedProps.ajaxurl = initElements.dataset.ajaxurl || ''
  sharedProps.platformName = initElements.dataset.platformName || ''
  await reloadStats()
}

onMounted(() => {
  init()
})
</script>

<style>
  .histo-app-front-stats h1 {
    text-align: left;
    color: var(--blue-france-sun-113-625);
  }
  .iframed .histo-app-front-stats h1 {
    display: none;
  }

  .histo-app-front-stats a {
    color: var(--blue-france-sun-113-625);
  }

  .loading {
    font-size: 2rem;
  }
</style>
