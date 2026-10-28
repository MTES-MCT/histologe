<template>
  <div class="histo-front-stats-territory fr-px-5w fr-pt-5w">
    <div class="fr-container">
      <h2>Evolution territoriale du mal-logement</h2>
      <label for="filter-territoires">Sélectionnez un territoire pour afficher ses statistiques</label>
      <HistoSelect
        id="filter-territoires"
        v-model="filters.territoire"
        @update:modelValue="emit('territoryChange')"
        inner-label="Territoire"
        :option-items="sharedState.territoiresList"
        />

      <div class="fr-container--fluid fr-py-5v">
        <div class="fr-grid-row fr-grid-row--gutters">
          <FrontStatsTerritoryItem size-class="7" v-model="filters.yearTypes.perMonth">
            <template #title>Nombre de signalements déposés</template>
            <template #graph>
              <HistoChartLine :items="chartItems('perMonth')" />
            </template>
          </FrontStatsTerritoryItem>

          <FrontStatsTerritoryItem size-class="5" v-model="filters.yearTypes.perStatut">
            <template #title>Répartition des signalements par statut</template>
            <template #graph>
              <HistoChartPie :items="chartItems('perStatut')" />
            </template>
          </FrontStatsTerritoryItem>

          <FrontStatsTerritoryItem size-class="12" v-model="filters.yearTypes.perMotifCloture">
            <template #title>Répartition des signalements fermés par motif de clôture</template>
            <template #graph>
              <HistoChartBar :items="chartItems('perMotifCloture')" index-axis="x" />
            </template>
          </FrontStatsTerritoryItem>

          <FrontStatsTerritoryItem size-class="4" v-model="filters.yearTypes.perDesordresCategories">
            <template #title>Désordres par catégorie</template>
            <template #graph>
              <HistoChartPie :items="chartItems('perDesordresCategories')" />
            </template>
          </FrontStatsTerritoryItem>

          <FrontStatsTerritoryItem size-class="4" :is-total-active="false" v-model="filters.yearTypes.perLogementDesordres">
            <template #title>Logement : désordres les plus courants*</template>
            <template #graph>
              <HistoChartDoughnut :items="chartItems('perLogementDesordres')" />
            </template>
          </FrontStatsTerritoryItem>

          <FrontStatsTerritoryItem size-class="4" :is-total-active="false" v-model="filters.yearTypes.perBatimentDesordres">
            <template #title>Bâtiment : désordres les plus courants*</template>
            <template #graph>
              <HistoChartDoughnut :items="chartItems('perBatimentDesordres')" />
            </template>
          </FrontStatsTerritoryItem>
        </div>
        <br>
        <i>* Les statistiques concernant les désordres les plus courants par catégorie sont disponibles depuis février 2024 uniquement.</i>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { store } from '../composables/useFrontStatsStore'
import type { ChartKey, StatsChartData } from '../types'
import HistoSelect from '../../common/HistoSelect.vue'
import HistoChartLine from '../../common/external/chartjs/HistoChartLine.vue'
import HistoChartBar from '../../common/external/chartjs/HistoChartBar.vue'
import HistoChartPie from '../../common/external/chartjs/HistoChartPie.vue'
import HistoChartDoughnut from '../../common/external/chartjs/HistoChartDoughnut.vue'
import FrontStatsTerritoryItem from './FrontStatsTerritoryItem.vue'

// Émissions
const emit = defineEmits<{
  territoryChange: []
}>()

// State
const sharedState = store.state
const filters = sharedState.filters

/**
 * Données du graphique pour la période sélectionnée (année en cours ou total)
 */
const chartItems = (key: ChartKey): StatsChartData => sharedState.stats.charts[key][filters.yearTypes[key]]
</script>

<style>
  div.histo-front-stats-territory {
    background-color: var(--blue-france-975-75);
  }

  div.histo-front-stats-territory h2 {
    color: var(--grey-50-1000);
    font-size: 32px;
    font-weight: bold;
  }

  div.histo-front-stats-territory label {
    color: var(--grey-200-850);
    font-size: 18px;
    display: block;
    padding-bottom: 8px;
  }

  div.histo-front-stats-territory .histo-select {
    display: block;
    max-width: 250px;
  }
</style>
