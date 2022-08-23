import { createApp } from 'vue'
import TheHistoAppStats from './components/stats/TheHistoAppStats.vue'
import '@vuepic/vue-datepicker/dist/main.css'

const app = createApp(TheHistoAppStats)
app.mount('#app-stats')
