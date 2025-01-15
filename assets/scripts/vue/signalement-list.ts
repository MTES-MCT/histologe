import { createApp } from 'vue'
import TheSignalementAppList from './components/signalement-view/TheSignalementAppList.vue'

const app = createApp(TheSignalementAppList)
const signalementListComponent = document.getElementById('app-signalement-view')
if (signalementListComponent !== null) {
  app.mount('#app-signalement-view')
}
