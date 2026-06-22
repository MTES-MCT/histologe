import { createApp } from 'vue'
import TheAddressesHistoryApp from './components/addresses-history/TheAddressesHistoryApp.vue'

const app = createApp(TheAddressesHistoryApp)
const addressesHistoryComponent = document.getElementById('app-addresses-history-view')
if (addressesHistoryComponent !== null) {
  app.mount('#app-addresses-history-view')
}
