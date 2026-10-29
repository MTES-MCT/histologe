import { createApp } from 'vue'
import FrontStatsApp from './components/front-stats/FrontStatsApp.vue'

const frontStatsComponent = document.getElementById('front-stats-app')
if (frontStatsComponent !== null) {
  createApp(FrontStatsApp).mount(frontStatsComponent)
}
