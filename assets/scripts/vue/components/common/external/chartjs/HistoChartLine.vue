<template>
  <div class="histo-chart-line histo-chart-item">
    <span :id="titleId" class="histo-chart-item-title"><slot name="title"></slot></span>
    <div
      tabindex="0"
      role="img"
      :aria-labelledby="labelledBy || titleId"
      aria-description="Utilisez les flèches pour parcourir les valeurs du graphique"
      @keydown.right.prevent="showNextChartValue"
      @keydown.down.prevent="showNextChartValue"
      @keydown.left.prevent="showPreviousChartValue"
      @keydown.up.prevent="showPreviousChartValue"
      @keydown.home.prevent="showFirstChartValue"
      @keydown.end.prevent="showLastChartValue"
      @keydown.esc="hideChartValue"
      @blur="hideChartValue"
      >
      <Line
        :chart-options="chartOptions"
        :chart-data="chartData"
        :chart-id="chartId"
        :plugins="plugins"
        :css-classes="cssClasses"
        :styles="styles"
        :width="width"
        :height="height"
        />
    </div>
    <details class="fr-mt-2w">
      <summary>Afficher les données</summary>
      <!-- Zone défilante focusable pour pouvoir la faire défiler au clavier -->
      <div class="table-scroll" tabindex="0" role="region" :aria-labelledby="labelledBy || titleId">
        <ChartDataTable
          :labels="chartData.labels"
          :values="chartData.datasets[0].data"
          :labelled-by="labelledBy || titleId"
          />
      </div>
    </details>
  </div>
</template>

<script lang="ts">
import { defineComponent, useId } from 'vue'
import { Line } from 'vue-chartjs'
import { showNextChartValue, showPreviousChartValue, showFirstChartValue, showLastChartValue, hideChartValue } from './chartKeyboardNav'
import ChartDataTable from '../../ChartDataTable.vue'
import { Chart as ChartJS, Title, Tooltip, Legend, LineElement, LinearScale, PointElement, CategoryScale, Filler } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, LineElement, LinearScale, PointElement, CategoryScale, Filler)

export default defineComponent({
  name: 'HistoChartLine',
  components: { Line, ChartDataTable },
  props: {
    chartId: {
      type: String,
      default: 'line-chart'
    },
    items: {
      type: Object,
      default: () => {}
    },
    width: {
      type: Number,
      default: 400
    },
    height: {
      type: Number,
      default: 300
    },
    cssClasses: {
      default: '',
      type: String
    },
    styles: {
      type: Object,
      default: () => {}
    },
    plugins: {
      type: Object,
      default: () => {}
    },
    labelledBy: {
      type: String,
      default: ''
    }
  },
  setup () {
    // Identifiant du titre, qui sert de libellé par défaut au graphique et à son tableau de données
    return { titleId: useId() }
  },
  data () {
    const inLabels = []
    const inData = []
    for (const i in this.items) {
      inLabels.push(i)
      inData.push(this.items[i])
    }
    return {
      chartData: {
        labels: inLabels,
        datasets: [
          {
            data: inData,
            cubicInterpolationMode: 'monotone',
            borderColor: '#000091',
            fill: true,
            backgroundColor: 'rgb(202, 202, 251, 0.7)',
            pointBackgroundColor: '#000091',
            pointHoverRadius: 5,
            pointHoverBorderWidth: 3,
            pointHoverBackgroundColor: '#FFFFFF'
          }
        ]
      },
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    }
  },
  methods: {
    showNextChartValue,
    showPreviousChartValue,
    showFirstChartValue,
    showLastChartValue,
    hideChartValue
  }
})
</script>

<style>
  .histo-chart-line .table-scroll {
    max-height: 26rem;
    overflow-y: auto;
  }
</style>
