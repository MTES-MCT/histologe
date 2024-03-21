<template>
  <div class="histo-chart-line histo-chart-item">
    <span class="histo-chart-item-title"><slot name="title"></slot></span>
    <div class="part_container">
      <line-chart
        color="blue-ecume"
        :x='chartLabels'
        :y=chartValues
        :name=name
        vlinename="Nombre de signalements"
        hlinename="Mois"
        ></line-chart>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'HistoChartLine',
  props: {
    chartId: {
      type: String,
      default: 'line-chart'
    },
    items: {
      type: Object,
      default: () => {}
    },
    name: {
      type: String,
      default: ''
    }
  },
  data () {
    const inLabels = []
    const inData = []
    for (const i in this.items) {
      inLabels.push(i)
      inData.push(this.items[i])
    }
    return {
      chartLabels: JSON.stringify(inLabels),
      chartValues: JSON.stringify(inData)
    }
  }
})
</script>

<style>
</style>
