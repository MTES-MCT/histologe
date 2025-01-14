import { createApp } from 'vue'
import TheSignalementCartoApp from './components/signalement-carto/TheSignalementCartoApp.vue'

const app = createApp(TheSignalementCartoApp)
const signalementCartoComponent = document.getElementById('app-signalement-carto')
if (signalementCartoComponent !== null) {
  app.mount('#app-signalement-carto')
}
