import { createApp } from 'vue'
import TheHistoAppDashboard from './components/dashboard/TheHistoAppDashboard.vue'

const app = createApp(TheHistoAppDashboard)
const appDashbordComponent = document.getElementById('app-dashboard')
if (appDashbordComponent !== null) {
  app.mount('#app-dashboard')
}
