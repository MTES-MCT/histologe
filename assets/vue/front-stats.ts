import { createApp } from 'vue'
import TheHistoAppFrontStats from './components/front-stats/TheHistoAppFrontStats.vue'
import '@vuepic/vue-datepicker/dist/main.css'

const app = createApp(TheHistoAppFrontStats)
const appStatsComponent = document.getElementById('app-front-stats')
if (null !== appStatsComponent) {
    app.mount('#app-front-stats')
}
