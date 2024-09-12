import { createApp } from 'vue'
import TheSignalementAppList from './components/signalement-list/TheSignalementAppList.vue'

const app = createApp(TheSignalementAppList)
const signalementListComponent = document.getElementById('app-signalement-list')
if (signalementListComponent !== null) {
  app.mount('#app-signalement-list')
}
