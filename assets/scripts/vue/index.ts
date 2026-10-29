import { createApp } from 'vue'
import * as Sentry from '@sentry/browser'
import '@vuepic/vue-datepicker/dist/main.css'

// Chargé à la demande : les statistiques embarquent chart.js et datatables.net, inutiles sur les autres pages
const statsComponent = document.getElementById('stats-app')
if (statsComponent !== null) {
  try {
    const { default: StatsApp } = await import('./components/stats/StatsApp.vue')
    createApp(StatsApp).mount(statsComponent)
  } catch (error) {
    console.error('Error loading stats app:', error)
    Sentry.captureException(error)
  }
}
