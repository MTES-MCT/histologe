import { createApp } from 'vue'
import TheHistoAppSignalementList from './components/signalement-list/TheHistoAppSignalementList.vue'

const app = createApp(TheHistoAppSignalementList)
const signalementListComponent = document.getElementById('app-signalement-list')
if (signalementListComponent !== null) {
  app.mount('#app-signalement-list')
}
