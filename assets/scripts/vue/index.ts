import { createApp } from 'vue'
import '@vuepic/vue-datepicker/dist/main.css'

// Chargé à la demande : les statistiques embarquent chart.js et datatables.net, inutiles sur les autres pages
const statsComponent = document.getElementById('app-stats')
if (statsComponent !== null) {
  import('./components/stats/TheHistoAppStats.vue').then(({ default: TheHistoAppStats }) => {
    createApp(TheHistoAppStats).mount(statsComponent)
  })
}
