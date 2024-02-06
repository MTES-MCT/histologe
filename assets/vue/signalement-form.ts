import { createApp } from 'vue'
import TheSignalementAppForm from './components/signalement-form/TheSignalementAppForm.vue'
// import '@vuepic/vue-datepicker/dist/main.css'

const app = createApp(TheSignalementAppForm)
const dashboardComponent = document.getElementById('app-signalement-form')
if (null !== dashboardComponent) {
    app.mount('#app-signalement-form')
}
