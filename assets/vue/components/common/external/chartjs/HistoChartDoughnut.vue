<template>
  <div class="histo-chart-doughnut histo-chart-item">
    <span class="histo-chart-item-title"><slot name="title"></slot></span>
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
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { Doughnut } from 'vue-chartjs'

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
    Doughnut
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
      default: 400
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
    }
  },
  data () {
    const inLabels = []
    const inData = []
    const inColors = []
    for (const i in this.items) {
      const { label, count, color } = this.items[i]
      const labelChunks = this.splitLabelIntoChunks(label, this.labelCharMax)
      inLabels.push(labelChunks)
      inData.push(count)
      inColors.push(color)
    }
    return {
      chartData: {
        labels: inLabels,
        datasets: [
          {
            data: inData,
            backgroundColor: inColors,
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
            position: 'bottom',
            align: 'start',
            labels: {
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
