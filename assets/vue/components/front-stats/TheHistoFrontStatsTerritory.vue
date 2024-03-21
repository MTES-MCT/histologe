<template>
  <div class="histo-front-stats-territory fr-px-5w fr-pt-5w">
    <div class="fr-container">
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
          <TheHistoFrontStatsTerritoryItem sizeClass="7" v-model="sharedState.filters.perMonthYearType" :onChange="handleChangePerMonth">
            <template #title>Signalements déposés</template>
            <template #graph>
              <HistoChartLine v-if="!isLoadingPerMonth" :items=perMonthData name="Signalements" />
            </template>
          </TheHistoFrontStatsTerritoryItem>

          <TheHistoFrontStatsTerritoryItem sizeClass="5" v-model="sharedState.filters.perStatutYearType" :onChange="handleChangePerStatut">
            <template #title>Statut du signalement</template>
            <template #graph>
              <HistoChartPie v-if="!isLoadingPerStatut" :items=perStatutData />
            </template>
          </TheHistoFrontStatsTerritoryItem>

          <TheHistoFrontStatsTerritoryItem sizeClass="7" v-model="sharedState.filters.perSituationYearType" :onChange="handleChangePerSituation">
            <template #title>Répartition par famille de désordres</template>
            <template #graph>
              <HistoChartBar v-if="!isLoadingPerSituation" :items=perSituationData indexAxis="x" />
            </template>
          </TheHistoFrontStatsTerritoryItem>

          <TheHistoFrontStatsTerritoryItem sizeClass="5" v-model="sharedState.filters.perMotifClotureYearType" :onChange="handleChangePerMotifCloture">
            <template #title>Motif de clôture</template>
            <template #graph>
              <HistoChartDoughnut v-if="!isLoadingPerMotifCloture" :items=perMotifClotureData />
            </template>
          </TheHistoFrontStatsTerritoryItem>
        </div>
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
    data: String,
    onUpdateFilter: Function
  },
  data () {
    return {
      sharedState: store.state,
      isLoadingPerMonth: false,
      isLoadingPerStatut: false,
      isLoadingPerSituation: false,
      isLoadingPerMotifCloture: false
    }
  },
  methods: {
    handleChangeTerritoire () {
      if (this.onUpdateFilter !== undefined) {
        this.onUpdateFilter()
      }
    },
    handleChangePerMonth () {
      this.isLoadingPerMonth = true
      setTimeout(() => { this.isLoadingPerMonth = false }, 100)
    },
    handleChangePerStatut () {
      this.isLoadingPerStatut = true
      setTimeout(() => { this.isLoadingPerStatut = false }, 100)
    },
    handleChangePerSituation () {
      this.isLoadingPerSituation = true
      setTimeout(() => { this.isLoadingPerSituation = false }, 100)
    },
    handleChangePerMotifCloture () {
      this.isLoadingPerMotifCloture = true
      setTimeout(() => { this.isLoadingPerMotifCloture = false }, 100)
    }
  },
  computed: {
    perMonthData () {
      if (this.sharedState.filters.perMonthYearType === 'year') {
        return this.sharedState.stats.countSignalementPerMonthThisYear
      }
      return this.sharedState.stats.countSignalementPerMonth
    },
    perStatutData () {
      if (this.sharedState.filters.perStatutYearType === 'year') {
        return this.sharedState.stats.countSignalementPerStatutThisYear
      }
      return this.sharedState.stats.countSignalementPerStatut
    },
    perSituationData () {
      if (this.sharedState.filters.perSituationYearType === 'year') {
        return this.sharedState.stats.countSignalementPerSituationThisYear
      }
      return this.sharedState.stats.countSignalementPerSituation
    },
    perMotifClotureData () {
      if (this.sharedState.filters.perMotifClotureYearType === 'year') {
        return this.sharedState.stats.countSignalementPerMotifClotureThisYear
      }
      return this.sharedState.stats.countSignalementPerMotifCloture
    }
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
