<template>
  <div class="histo-chart-pie histo-chart-item">
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
      <Pie
        :chart-options="chartOptions"
        :chart-data="chartData"
        :chart-id="chartId"
        :dataset-id-key="datasetIdKey"
        :plugins="plugins"
        :css-classes="cssClasses"
        :styles="styles"
        :width="width"
        :height="height"
        />
    </div>
    <details class="fr-mt-2w">
      <summary>Afficher les données</summary>
      <ChartDataTable
        :labels="chartData.labels"
        :values="chartData.datasets[0].data"
        :labelled-by="labelledBy || titleId"
        />
    </details>
  </div>
</template>

<script lang="ts">
import { defineComponent, useId } from 'vue'
import { Pie } from 'vue-chartjs'
import { showNextChartValue, showPreviousChartValue, showFirstChartValue, showLastChartValue, hideChartValue } from './chartKeyboardNav'
import ChartDataTable from '../../ChartDataTable.vue'
import { createChartPattern } from './chartPatterns'

import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  CategoryScale
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale)

export default defineComponent({
  name: 'HistoChartPie',
  components: {
    Pie,
    ChartDataTable
  },
  props: {
    chartId: {
      type: String,
      default: 'pie-chart'
    },
    datasetIdKey: {
      type: String,
      default: 'label'
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
      default: 400
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
      type: Array,
      default: () => []
    },
    usePatterns: {
      type: Boolean,
      default: false
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
    const inColors = []
    const inBackgrounds = []
    for (const i in this.items) {
      inLabels.push(this.items[i].label)
      inData.push(this.items[i].count)
      inColors.push(this.items[i].color)
      inBackgrounds.push(this.usePatterns ? createChartPattern(this.items[i].color, inColors.length - 1) : this.items[i].color)
    }
    return {
      chartData: {
        labels: inLabels,
        datasets: [
          {
            data: inData,
            backgroundColor: inBackgrounds,
            hoverBorderWidth: 10,
            hoverBorderColor: inColors,
            hoverOffset: 5
          }
        ]
      },
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            // Le masquage d'une valeur au clic sur la légende n'est pas utilisable au clavier
            onClick: null,
            position: 'bottom',
            labels: {
              boxWidth: 40,
              boxHeight: 24
            }
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
