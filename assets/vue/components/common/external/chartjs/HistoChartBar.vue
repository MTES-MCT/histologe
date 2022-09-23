<template>
  <div class="histo-chart-bar histo-chart-item">
    <span class="histo-chart-item-title"><slot name="title"></slot></span>
    <Bar
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
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

export default {
  name: 'HistoChartBar',
  components: { Bar },
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
      type: Object,
      default: {}
    },
    width: {
      type: Number,
      default: 400
    },
    height: {
      type: Number,
      default: 250
    },
    cssClasses: {
      default: '',
      type: String
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
      type: Object,
      default: () => {}
    }
  },
  data() {
    let inLabels = []
    let inData = []
    for (let i in this.items) {
      let wordsBuffer = []
      let labelBuffer = ''
      const words = i.split(' ')
      for (let j = 0; j < words.length; j++) {
        if (labelBuffer !== '') {
          labelBuffer += ' '
        }
        if (labelBuffer.length + words[j].length < 50) {
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
      chartData: {
        labels: inLabels,
        datasets: [ { data: inData } ]
      },
      chartOptions: {
        responsive: true,
		    indexAxis: this.indexAxis,
        backgroundColor: '#000091',
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              title: function(context) {
                return context[0].label.split(' ,').join('\n')
              }
            }
          },
        }
      }
    }
  }
}
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
