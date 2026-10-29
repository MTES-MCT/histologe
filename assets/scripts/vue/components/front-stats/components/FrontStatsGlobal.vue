<template>
  <div class="front-stats-global fr-container">
    <div class="fr-container--fluid fr-px-5w fr-my-10v">
      <div class="fr-grid-row fr-grid-row--gutters">
        <div class="fr-col-12 fr-col-lg-3">
          <FrontStatsDetailsItem
            v-for="item in items"
            :key="item.title"
            :value="item.value"
            >
            <template #title>{{ item.title }}</template>
          </FrontStatsDetailsItem>
        </div>
        <div class="fr-col-12 fr-col-lg-9">
          <FrontStatsFranceMap :data="stats.countSignalementPerTerritory" />
        </div>
      </div>
    </div>

    <hr>

    <div class="fr-mt-5w fr-mb-7w imported-data">
      <h2>Données historiques</h2>
      <p>
        <span>{{ stats.countImported }}</span> signalements importés
        <br>
        Ces données correspondent aux signalements recensés sur les territoires avant le déploiement de la plateforme {{ sharedProps.platformName }}.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { store } from '../composables/useFrontStatsStore'
import FrontStatsDetailsItem from './FrontStatsDetailsItem.vue'
import FrontStatsFranceMap from './FrontStatsFranceMap.vue'

// State
const sharedProps = store.props
const stats = computed(() => store.state.stats)

const items = computed(() => [
  { title: 'foyers sortis du mal-logement', value: `${stats.value.countSignalementResolus}` },
  { title: 'signalements enregistrés', value: `${stats.value.countSignalement}` },
  { title: 'territoires déployés', value: `${stats.value.countTerritory}` },
  { title: 'taux de dossiers traités ou en cours de traitement', value: `${stats.value.percentValidation} %` },
  { title: 'taux de clôture des signalements', value: `${stats.value.percentCloture} %` },
  { title: 'taux de signalements refusés', value: `${stats.value.percentRefused} %` }
])
</script>

<style>
.imported-data span {
  font-weight: bold;
  color: var(--blue-france-sun-113-625);
}
</style>
