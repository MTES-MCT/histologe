import { createApp } from 'vue'
import FrontStatsApp from './components/front-stats/FrontStatsApp.vue'

const frontStatsComponent = document.getElementById('app-front-stats')
if (frontStatsComponent !== null) {
  createApp(FrontStatsApp).mount(frontStatsComponent)
}
