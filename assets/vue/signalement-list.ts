import { createApp } from 'vue'
import TheHistoAppSignalementList from './components/signalement-list/TheHistoAppSignalementList.vue'
import '@vuepic/vue-datepicker/dist/main.css'

const app = createApp(TheHistoAppSignalementList)
const signalementListComponent = document.getElementById('app-signalement-list')
if (signalementListComponent !== null) {
    app.mount('#app-signalement-list')
}

