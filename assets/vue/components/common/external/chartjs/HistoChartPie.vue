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
    datasetIdKey: {
      type: String,
      default: 'label'
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
    let inColors = []
    for (let i in this.items) {
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
            hoverBorderColor: '#A558A0',
          }
        ]
      },
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
         legend: {
            position: 'bottom'
         }
        }
      }
    }
  }
})
</script>
