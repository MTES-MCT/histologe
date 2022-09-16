<template>
  <div class="histo-front-stats-territory fr-px-5w fr-pt-5w">
    <h2>Evolution territoriale du logement</h2>
    <label for="filter-territoires">Sélectionnez un territoire pour afficher ses statistiques</label>
    <HistoSelect
      id="filter-territoires"
      v-model="sharedState.filters.territoire"
      @update:modelValue="handleChangeTerritoire"
      inner-label="Territoire"
      :option-items=sharedState.filters.territoiresList
      />

    <div class="fr-container--fluid fr-my-10v">
      <div class="fr-grid-row fr-grid-row--gutters">
        <TheHistoFrontStatsTerritoryItem sizeClass="7">
          <template #title>Signalements déposés</template>
          <template #graph>
            <HistoChartLine :items="sharedState.stats.countSignalementPerMonth" />
          </template>
        </TheHistoFrontStatsTerritoryItem>

        <TheHistoFrontStatsTerritoryItem sizeClass="5">
          <template #title>Statut du signalement</template>
          <template #graph>
            <HistoChartPie :items=sharedState.stats.countSignalementPerStatut />
          </template>
        </TheHistoFrontStatsTerritoryItem>

        <TheHistoFrontStatsTerritoryItem sizeClass="7">
          <template #title>Répartition par famille de désordres</template>
          <template #graph>
            <HistoChartBar :items=sharedState.stats.countSignalementPerSituation />
          </template>
        </TheHistoFrontStatsTerritoryItem>

        <TheHistoFrontStatsTerritoryItem sizeClass="5">
          <template #title>Motif de clôture</template>
          <template #graph>
            <HistoChartDoughnut :items=sharedState.stats.countSignalementPerCriticitePercent />
          </template>
        </TheHistoFrontStatsTerritoryItem>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import TheHistoFrontStatsTerritoryItem from './TheHistoFrontStatsTerritoryItem.vue'
import HistoSelect from '../common/HistoSelect.vue'
import HistoChartPie from '../common/external/chartjs/HistoChartPie.vue'
import HistoChartBar from '../common/external/chartjs/HistoChartBar.vue'
import HistoChartDoughnut from '../common/external/chartjs/HistoChartDoughnut.vue'
import HistoChartLine from '../common/external/chartjs/HistoChartLine.vue'

export default defineComponent({
  name: 'TheHistoFrontStatsTerritory',
  components: {
    HistoSelect,
    TheHistoFrontStatsTerritoryItem,
    HistoChartPie,
    HistoChartBar,
    HistoChartDoughnut,
    HistoChartLine
},
  props: {
    data: String
  },
  data () {
    return {
			sharedState: store.state,
    }
  },
  methods: {
    handleChangeTerritoire () {
      console.log('handleChangeTerritoire')
    }
  },
  computed: {
  }
})
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
