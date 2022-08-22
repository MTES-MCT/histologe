import { createApp } from 'vue'
import TheHistoAppStats from './components/stats/TheHistoAppStats.vue'

import Vue3EasyDataTable from 'vue3-easy-data-table'
import 'vue3-easy-data-table/dist/style.css'

const app = createApp(TheHistoAppStats)
app.component('EasyDataTable', Vue3EasyDataTable)
app.mount('#app-stats')
