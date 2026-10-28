import { createApp } from 'vue'
import * as Sentry from '@sentry/browser'
import '@vuepic/vue-datepicker/dist/main.css'

// Chargé à la demande : les statistiques embarquent chart.js et datatables.net, inutiles sur les autres pages
const mountStatsApp = async (container: HTMLElement): Promise<void> => {
  try {
    const { default: StatsApp } = await import('./components/stats/StatsApp.vue')
    createApp(StatsApp).mount(container)
  } catch (error) {
    console.error('Error loading stats app:', error)
    Sentry.captureException(error)
  }
}

const statsComponent = document.getElementById('app-stats')
if (statsComponent !== null) {
  void mountStatsApp(statsComponent)
}
