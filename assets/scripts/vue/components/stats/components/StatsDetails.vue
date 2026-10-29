<template>
  <section class="stats-details">
    <div class="fr-container--fluid fr-my-10v">
      <div class="fr-grid-row fr-grid-row--gutters">
        <StatsDetailsItem :value="countSignalement" color="blue">
          <template #title>Nb. signalements</template>
        </StatsDetailsItem>

        <StatsDetailsItem :value="averageCriticite" color="blue">
          <template #title>Criticité moyenne</template>
        </StatsDetailsItem>

        <div class="fr-col-12 histo-chart-line">
          <HistoChartLine :items="stats.countSignalementPerMonth">
            <template #title>Nombre total de signalements</template>
          </HistoChartLine>
        </div>

        <div class="fr-col-12 fr-col-lg-8">
          <div class="fr-mb-3w" v-if="sharedState.options.canSeePerPartenaire">
            <HistoDataTable :headers="PARTENAIRE_HEADERS" :items="stats.countSignalementPerPartenaire">
              <template #title>Répartition par partenaires (uniquement visible par les reponsables de territoires)</template>
              <template #description>Cliquez sur l'en-tête d'une colonne pour trier les résultats</template>
            </HistoDataTable>
          </div>

          <div class="fr-mb-3w">
            <HistoChartBar :items="stats.countSignalementPerSituation">
              <template #title>Répartition par famille de désordres</template>
            </HistoChartBar>
          </div>

          <div>
            <HistoChartBar :items="stats.countSignalementPerCriticite">
              <template #title>Désordres les plus renseignés</template>
            </HistoChartBar>
          </div>
        </div>

        <div class="fr-col-12 fr-col-lg-4">
          <div class="fr-mb-3w">
            <HistoChartPie :items="stats.countSignalementPerStatut">
              <template #title>Répartition par statut</template>
            </HistoChartPie>
          </div>

          <div class="fr-mb-3w">
            <HistoChartDoughnut :items="stats.countSignalementPerCriticitePercent">
              <template #title>Répartition par criticité</template>
            </HistoChartDoughnut>
          </div>

          <div class="fr-mb-3w">
            <HistoChartDoughnut :items="stats.countSignalementPerVisite">
              <template #title>Visite effectuée</template>
            </HistoChartDoughnut>
          </div>

          <div>
            <HistoChartDoughnut :items="stats.countSignalementPerMotifCloture" :height="800">
              <template #title>Motif de clôture</template>
            </HistoChartDoughnut>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { store } from '../composables/useStatsStore'
import { formatStatValue } from '../utils/formatters'
import HistoDataTable from '../../common/external/HistoDataTable.vue'
import HistoChartLine from '../../common/external/chartjs/HistoChartLine.vue'
import HistoChartBar from '../../common/external/chartjs/HistoChartBar.vue'
import HistoChartPie from '../../common/external/chartjs/HistoChartPie.vue'
import HistoChartDoughnut from '../../common/external/chartjs/HistoChartDoughnut.vue'
import StatsDetailsItem from './StatsDetailsItem.vue'

const PARTENAIRE_HEADERS = [
  'Partenaire',
  'Signalements',
  'En attente',
  'En cours',
  'Fermés'
]

// State
const sharedState = store.state
const stats = computed(() => sharedState.stats)

const countSignalement = computed(() => formatStatValue(stats.value.countSignalementFiltered, '0'))
const averageCriticite = computed(() => formatStatValue(stats.value.averageCriticiteFiltered, '-', ' %'))
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
