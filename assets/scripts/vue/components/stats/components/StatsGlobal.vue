<template>
  <section class="stats-global">
    <h2 class="fr-h3 fr-mb-5v">Statistiques globales</h2>

    <div class="fr-container--fluid filter-territories fr-mb-5v" v-if="sharedState.options.canFilterTerritoires">
      <div class="fr-grid-row fr-grid-row--gutters">
        <div class="fr-hidden fr-unhidden-md fr-col-md-9">
        </div>
        <div class="fr-col-12 fr-col-md-3">
          <HistoSelect
            id="filter-territoires"
            v-model="sharedState.filters.territoire"
            @update:modelValue="emit('territoryChange')"
            :option-items="sharedState.options.territoiresList"
            >
            <template #label>Territoire</template>
          </HistoSelect>
        </div>
      </div>
    </div>

    <p class="fr-mb-5w">
      Les statistiques globales comprennent l'intégralité de vos signalements, depuis la mise en place de la plateforme.
    </p>

    <div class="fr-container--fluid fr-my-10v">
      <div class="fr-grid-row fr-grid-row--gutters">
        <StatsDetailsItem
          v-for="item in items"
          :key="item.title"
          :value="item.value"
          color="purple"
          >
          <template #title>{{ item.title }}</template>
        </StatsDetailsItem>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { store } from '../composables/useStatsStore'
import { formatStatValue } from '../utils/formatters'
import HistoSelect from '../../common/HistoSelect.vue'
import StatsDetailsItem from './StatsDetailsItem.vue'

// Émissions
const emit = defineEmits<{
  territoryChange: []
}>()

// State
const sharedState = store.state

const items = computed(() => {
  const stats = sharedState.stats
  return [
    { title: 'Nb. signalements', value: formatStatValue(stats.countSignalement, '0') },
    { title: 'Criticité moyenne', value: formatStatValue(stats.averageCriticite, '-', ' %') },
    { title: 'Délai validation moy.', value: formatStatValue(stats.averageDaysValidation, '-', ' jours') },
    { title: 'Délai clôture moy.', value: formatStatValue(stats.averageDaysClosure, '-', ' jours') },
    { title: 'Nb. signalements refusés', value: formatStatValue(stats.countSignalementsRefuses, '0') },
    { title: 'Nb. signalements archivés', value: formatStatValue(stats.countSignalementsArchives, '0') }
  ]
})
</script>

<style>
  @media (min-width: 768px) {
    .filter-territories {
      margin-top: -65px;
    }
  }
</style>
