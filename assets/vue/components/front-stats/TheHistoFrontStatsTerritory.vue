<template>
  <div class="histo-front-stats-territory fr-px-5w fr-pt-5w">
    <div class="fr-container">
      <h2>Evolution territoriale du mal-logement</h2>
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
            <template #title>Nombre de signalements déposés</template>
            <template #graph>
              <HistoChartLine v-if="!isLoadingPerMonth" :items=perMonthData />
            </template>
          </TheHistoFrontStatsTerritoryItem>

          <TheHistoFrontStatsTerritoryItem sizeClass="5" v-model="sharedState.filters.perStatutYearType" :onChange="handleChangePerStatut">
            <template #title>Répartition des signalements par statut</template>
            <template #graph>
              <HistoChartPie v-if="!isLoadingPerStatut" :items=perStatutData />
            </template>
          </TheHistoFrontStatsTerritoryItem>

          <TheHistoFrontStatsTerritoryItem sizeClass="12" v-model="sharedState.filters.perMotifClotureYearType" :onChange="handleChangePerMotifCloture">
            <template #title>Répartition des signalements clôturés par motif de clôture</template>
            <template #graph>
              <HistoChartBar v-if="!isLoadingPerMotifCloture" :items=perMotifClotureData indexAxis="x"  />
            </template>
          </TheHistoFrontStatsTerritoryItem>

          <TheHistoFrontStatsTerritoryItem
            sizeClass="4"
            :isTotalActive="false"
            v-model="sharedState.filters.perLogementDesordresYearType"
            :onChange="handleChangePerLogementDesordres"
          >
            <template #title>Logement : désordres les plus courants*</template>
            <template #graph>
              <HistoChartDoughnut v-if="!isLoadingPerLogementDesordres" :items=perLogementDesordresData />
            </template>
          </TheHistoFrontStatsTerritoryItem>

          <TheHistoFrontStatsTerritoryItem
            sizeClass="4"
            v-model="sharedState.filters.perBatimentDesordresYearType"
            :onChange="handleChangePerBatimentDesordres"
            :isTotalActive="false"
          >
            <template #title>Bâtiment : désordres les plus courants*</template>
            <template #graph>
              <HistoChartDoughnut v-if="!isLoadingPerBatimentDesordres" :items=perBatimentDesordresData />
            </template>
          </TheHistoFrontStatsTerritoryItem>
        </div>
        <i>* Les statistiques concernant les désordres les plus courants par catégorie sont disponibles depuis février 2024 uniquement.</i>
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
      isLoadingPerMotifCloture: false,
      isLoadingPerLogementDesordres: false,
      isLoadingPerBatimentDesordres: false
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
    handleChangePerMotifCloture () {
      this.isLoadingPerMotifCloture = true
      setTimeout(() => { this.isLoadingPerMotifCloture = false }, 100)
    },
    handleChangePerLogementDesordres () {
      this.isLoadingPerLogementDesordres = true
      setTimeout(() => { this.isLoadingPerLogementDesordres = false }, 100)
    },
    handleChangePerBatimentDesordres () {
      this.isLoadingPerBatimentDesordres = true
      setTimeout(() => { this.isLoadingPerBatimentDesordres = false }, 100)
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
    perMotifClotureData () {
      if (this.sharedState.filters.perMotifClotureYearType === 'year') {
        return this.sharedState.stats.countSignalementPerMotifClotureThisYear
      }
      return this.sharedState.stats.countSignalementPerMotifCloture
    },
    perLogementDesordresData () {
      if (this.sharedState.filters.perLogementDesordresYearType === 'year') {
        return this.sharedState.stats.countSignalementPerLogementDesordresThisYear
      }
      return this.sharedState.stats.countSignalementPerLogementDesordres
    },
    perBatimentDesordresData () {
      if (this.sharedState.filters.perBatimentDesordresYearType === 'year') {
        return this.sharedState.stats.countSignalementPerBatimentDesordresThisYear
      }
      return this.sharedState.stats.countSignalementPerBatimentDesordres
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
