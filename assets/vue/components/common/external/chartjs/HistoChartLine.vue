<template>
  <div class="histo-chart-line histo-chart-item">
    <span class="histo-chart-item-title"><slot name="title"></slot></span>
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
</template>

<script>
import { defineComponent } from 'vue'
import { Line } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, LineElement, LinearScale, PointElement, CategoryScale, Plugin, Filler } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, LineElement, LinearScale, PointElement, CategoryScale, Filler)

export default defineComponent({
  name: 'HistoChartLine',
  components: { Line },
  props: {
    chartId: {
      type: String,
      default: 'line-chart'
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
  }
})
</script>

<style>
</style>
