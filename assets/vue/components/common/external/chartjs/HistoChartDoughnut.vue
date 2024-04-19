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

<script>
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
    }
  },
  data () {
    const inLabels = []
    const inData = []
    const inColors = []
    for (const i in this.items) {
      inLabels.push(this.items[i].label)
      inData.push(this.items[i].count)
      inColors.push(this.items[i].color)
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
            align: 'start'
          }
        }
      }
    }
  }
})
</script>
