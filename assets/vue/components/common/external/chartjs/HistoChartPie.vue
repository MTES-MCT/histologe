<template>
  <div class="histo-chart-pie histo-chart-item">
    <span class="histo-chart-item-title"><slot name="title"></slot></span>
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
</template>

<script>
import { defineComponent } from 'vue'
import { Pie } from 'vue-chartjs'

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
    Pie
  },
  props: {
    chartId: {
      type: String,
      default: 'pie-chart'
    },
    items: {
      type: Object,
      default: {}
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
  data() {
    let inLabels = []
    let inData = []
    for (let i in this.items) {
      inLabels.push(i)
      inData.push(this.items[i])
    }
    return {
      chartData: {
        labels: inLabels,
        datasets: [
          {
            backgroundColor: ['#21AB8EA6', '#000091A6', '#E4794AA6', '#A558A0A6', '#CACAFBBF'],
            data: inData
          }
        ]
      },
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false
      }
    }
  }
})
</script>
