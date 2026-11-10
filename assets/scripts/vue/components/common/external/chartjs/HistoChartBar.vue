<template>
  <div class="histo-chart-bar histo-chart-item">
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
      <Bar
        :chart-options="chartOptions"
        :chart-data="chartData"
        :chart-id="chartId"
        :dataset-id-key="datasetIdKey"
        :plugins="showValues ? [...plugins, valueLabelsPlugin] : plugins"
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
import { Bar } from 'vue-chartjs'
import { showNextChartValue, showPreviousChartValue, showFirstChartValue, showLastChartValue, hideChartValue } from './chartKeyboardNav'
import ChartDataTable from '../../ChartDataTable.vue'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'
import type { Plugin } from 'chart.js'

const valueLabelsPlugin: Plugin<'bar'> = {
  id: 'barValueLabels',
  afterDatasetsDraw (chart) {
    const { ctx } = chart
    ctx.save()
    ctx.font = '12px Marianne, Arial, sans-serif'
    ctx.fillStyle = '#3A3A3A'
    ctx.textBaseline = 'middle'
    chart.data.datasets.forEach((dataset, datasetIndex) => {
      if (!chart.isDatasetVisible(datasetIndex)) {
        return
      }
      chart.getDatasetMeta(datasetIndex).data.forEach((bar, index) => {
        const value = dataset.data[index]
        if (typeof value === 'number') {
          ctx.fillText(value.toLocaleString('fr-FR'), bar.x + 8, bar.y)
        }
      })
    })
    ctx.restore()
  }
}

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

export default defineComponent({
  name: 'HistoChartBar',
  components: { Bar, ChartDataTable },
  props: {
    chartId: {
      type: String,
      default: 'bar-chart'
    },
    datasetIdKey: {
      type: String,
      default: 'label'
    },
    items: {
      type: Object
    },
    width: {
      type: Number,
      default: 400
    },
    height: {
      type: Number,
      default: 250
    },
    maintainAspectRatio: {
      type: Boolean,
      default: true
    },
    labelCharMax: {
      type: Number,
      default: 50
    },
    showValues: {
      type: Boolean,
      default: false
    },
    cssClasses: {
      type: String,
      default: ''
    },
    indexAxis: {
      default: 'y',
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
      const wordsBuffer = []
      let labelBuffer = ''
      const words = i.split(' ')
      for (let j = 0; j < words.length; j++) {
        if (labelBuffer !== '') {
          labelBuffer += ' '
        }
        if (labelBuffer.length + words[j].length < this.labelCharMax) {
          labelBuffer += words[j]
        } else {
          wordsBuffer.push(labelBuffer)
          labelBuffer = words[j]
        }
      }
      if (labelBuffer !== '') {
        wordsBuffer.push(labelBuffer)
      }
      inLabels.push(wordsBuffer)
      inData.push(this.items[i])
    }
    return {
      valueLabelsPlugin,
      chartData: {
        labels: inLabels,
        datasets: [{ data: inData }]
      },
      chartOptions: {
        responsive: true,
        maintainAspectRatio: this.maintainAspectRatio,
        indexAxis: this.indexAxis,
        layout: {
          padding: {
            right: this.showValues ? 70 : 0
          }
        },
        backgroundColor: '#000091',
        hoverBorderColor: '#A558A0',
        hoverBorderWidth: 3,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              title: function (context: any) {
                return context[0].label.split(' ,').join('\n')
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
    hideChartValue
  }
})
</script>

<style>
.histo-chart-bar {
  text-align: center;
  background: #FFFFFF;
  padding: 1rem 0.5rem;
}

.histo-chart-bar .histo-chart-bar-title {
  font-weight: bold;
  font-size: 0.8rem;
}
</style>
