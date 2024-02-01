import { createApp } from 'vue'
import TheHistoAppStats from './components/stats/TheHistoAppStats.vue'
import '@vuepic/vue-datepicker/dist/main.css'

const app = createApp(TheHistoAppStats)
const statsComponent = document.getElementById('app-stats')
if (null !== statsComponent) {
    app.mount('#app-stats')
}
