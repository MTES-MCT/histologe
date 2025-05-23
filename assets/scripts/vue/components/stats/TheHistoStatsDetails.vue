<template>
  <section class="histo-stats-details">
    <div class="fr-container--fluid fr-my-10v">
      <div class="fr-grid-row fr-grid-row--gutters">
        <TheHistoStatsDetailsItem :data=strCountSignalement color="blue">
          <template #title>Nb. signalements</template>
        </TheHistoStatsDetailsItem>

        <TheHistoStatsDetailsItem :data=strAverageCriticite color="blue">
          <template #title>Criticité moyenne</template>
        </TheHistoStatsDetailsItem>

        <div class="fr-col-12 histo-chart-line">
          <HistoChartLine :items=sharedState.stats.countSignalementPerMonth>
            <template #title>Nombre total de signalements</template>
          </HistoChartLine>
        </div>

        <div class="fr-col-12 fr-col-lg-8">
          <div class="fr-mb-3w" v-if="sharedState.filters.canSeePerPartenaire">
            <HistoDataTable :headers=countSignalementPerPartenaireHeaders :items=sharedState.stats.countSignalementPerPartenaire>
              <template #title>Répartition par partenaires (uniquement visible par les reponsables de territoires)</template>
              <template #description>Cliquez sur l'en-tête d'une colonne pour trier les résultats</template>
            </HistoDataTable>
          </div>

          <div class="fr-mb-3w">
            <HistoChartBar :items=sharedState.stats.countSignalementPerSituation>
              <template #title>Répartition par famille de désordres</template>
            </HistoChartBar>
          </div>

          <div>
            <HistoChartBar :items=sharedState.stats.countSignalementPerCriticite>
              <template #title>Désordres les plus renseignés</template>
            </HistoChartBar>
          </div>
        </div>

        <div class="fr-col-12 fr-col-lg-4">
          <div class="fr-mb-3w">
            <HistoChartPie :items=sharedState.stats.countSignalementPerStatut>
              <template #title>Répartition par statut</template>
            </HistoChartPie>
          </div>

          <div class="fr-mb-3w">
            <HistoChartDoughnut :items=sharedState.stats.countSignalementPerCriticitePercent>
              <template #title>Répartition par criticité</template>
            </HistoChartDoughnut>
          </div>

          <div class="fr-mb-3w">
            <HistoChartDoughnut :items=sharedState.stats.countSignalementPerVisite>
              <template #title>Visite effectuée</template>
            </HistoChartDoughnut>
          </div>

          <div>
            <HistoChartDoughnut :items=sharedState.stats.countSignalementPerMotifCloture>
              <template #title>Motif de clôture</template>
            </HistoChartDoughnut>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import HistoDataTable from '../common/external/HistoDataTable.vue'
import HistoChartLine from '../common/external/chartjs/HistoChartLine.vue'
import HistoChartBar from '../common/external/chartjs/HistoChartBar.vue'
import HistoChartPie from '../common/external/chartjs/HistoChartPie.vue'
import HistoChartDoughnut from '../common/external/chartjs/HistoChartDoughnut.vue'
import TheHistoStatsDetailsItem from './TheHistoStatsDetailsItem.vue'

export default defineComponent({
  name: 'TheHistoStatsDetails',
  props: {
    onChange: { type: Function }
  },
  components: {
    HistoDataTable,
    HistoChartLine,
    HistoChartBar,
    HistoChartPie,
    HistoChartDoughnut,
    TheHistoStatsDetailsItem
  },
  data () {
    return {
      sharedState: store.state,
      countSignalementPerPartenaireHeaders: [
        'Partenaire',
        'Signalements',
        'En attente',
        'En cours',
        'Fermés'
      ]
    }
  },
  computed: {
    strCountSignalement () {
      const countSignalement:string = this.sharedState.stats.countSignalementFiltered !== undefined ? this.sharedState.stats.countSignalementFiltered.toString() : '0'
      return countSignalement
    },
    strAverageCriticite () {
      const averageCriticite:string = this.sharedState.stats.averageCriticiteFiltered !== undefined ? this.sharedState.stats.averageCriticiteFiltered.toString() : '-'
      return averageCriticite + ' %'
    }
  }
})
</script>

<style>
  .stat-general {
    text-align: center;
    font-weight: bold;
    background: #FFF;
    padding: 1rem 0.5rem;
  }
  .stat-general p:first-child {
    font-size: 0.8rem;
    color: var(--grey-200-850);
  }
  .stat-general p:last-child {
    font-size: 2rem;
    color: var(--blue-france-sun-113-625);
  }

  .histo-chart-item, .histo-data-table {
    text-align: center;
    background: #FFFFFF;
    padding: 0.5rem 0.5rem;
  }

  .histo-chart-item .histo-chart-item-title, .histo-data-table .histo-data-table-title {
    font-weight: bold;
    font-size: 0.8rem;
  }

  .histo-data-table .histo-data-table-description {
    font-style: italic;
    font-size: 0.8rem;
  }

  .histo-chart-line {
    border: 1px solid var(--blue-france-sun-113-625);
  }
</style>
