import { createApp } from 'vue'
import FrontStatsApp from './components/front-stats/FrontStatsApp.vue'
// Lorsque les stats sont intégrées en iframe (ex : site vitrine), transmet la hauteur
// du contenu à la page parente pour qu'elle redimensionne l'iframe (zoom texte, responsive...).
import 'iframe-resizer/js/iframeResizer.contentWindow'

const frontStatsComponent = document.getElementById('front-stats-app')
if (frontStatsComponent !== null) {
  createApp(FrontStatsApp).mount(frontStatsComponent)
}
