import { createApp } from 'vue'
import AddressesHistory from './components/addresses-history/AddressesHistory.vue'

const appElement = document.getElementById('app-addresses-history-view')
if (appElement) {
  createApp(AddressesHistory).mount(appElement)
}
