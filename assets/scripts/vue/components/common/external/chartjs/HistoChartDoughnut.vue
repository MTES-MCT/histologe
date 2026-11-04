<template>
  <div class="histo-chart-doughnut histo-chart-item">
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
      <Doughnut
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
import { Doughnut } from 'vue-chartjs'
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
  name: 'HistoChartDoughnut',
  components: {
    Doughnut,
    ChartDataTable
  },
  props: {
    chartId: {
      type: String,
      default: 'doughnut-chart'
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
      default: 550
    },
    cssClasses: {
      type: String,
      default: ''
    },
    styles: {
      type: Object,
      default: () => {}
    },
    plugins: {
      type: Array,
      default: () => []
    },
    labelCharMax: {
      type: Number,
      default: 50
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
      const { label, count, color } = this.items[i]
      const labelChunks = this.splitLabelIntoChunks(label, this.labelCharMax)
      inLabels.push(labelChunks)
      inData.push(count)
      inColors.push(color)
      inBackgrounds.push(this.usePatterns ? createChartPattern(color, inColors.length - 1) : color)
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
            align: 'start',
            labels: {
              boxWidth: 40,
              boxHeight: 24,
              padding: 20,
              font: {
                lineHeight: 0.9
              }
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
    hideChartValue,
    splitLabelIntoChunks (label: string, labelCharMax: number) {
      const labelChunks = []
      let currentChunk = ''
      let currentLength = 0

      const words = label.split(' ')
      for (const word of words) {
        if (currentLength + word.length + 1 <= labelCharMax) {
          currentChunk += word + ' '
          currentLength += word.length + 1
        } else {
          labelChunks.push(currentChunk.trim())
          currentChunk = word + ' '
          currentLength = word.length + 1
        }
      }

      if (currentChunk.trim() !== '') {
        labelChunks.push(currentChunk.trim())
      }

      return labelChunks
    }
  }
})
</script>
