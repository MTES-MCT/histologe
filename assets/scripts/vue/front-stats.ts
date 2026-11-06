import { createApp } from 'vue'
import TheHistoAppFrontStats from './components/front-stats/TheHistoAppFrontStats.vue'
import '@vuepic/vue-datepicker/dist/main.css'
// Lorsque les stats sont intégrées en iframe (ex : site vitrine), transmet la hauteur
// du contenu à la page parente pour qu'elle redimensionne l'iframe (zoom texte, responsive...).
import 'iframe-resizer/js/iframeResizer.contentWindow'

const app = createApp(TheHistoAppFrontStats)
const appStatsComponent = document.getElementById('app-front-stats')
if (appStatsComponent !== null) {
  app.mount('#app-front-stats')
}
