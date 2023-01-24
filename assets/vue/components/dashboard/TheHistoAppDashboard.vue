<template>
  <div
    id="app-dashboard"
    class="histo-app-dashboard fr-pt-5w"
    :data-ajaxurl-filter="sharedProps.ajaxurlFilter"
    :data-ajaxurl-partners="sharedProps.ajaxurlPartners"
    :data-ajaxurl-signalements-nosuivi="sharedProps.ajaxurlSignalementsNosuivi"
    :data-ajaxurl-signalements-per-territoire="sharedProps.ajaxurlSignamentsPerTerritoire"
    :data-ajaxurl-connections-esabora="sharedProps.ajaxurlConnectionsEsabora"
    >
    <h1>Tableau de bord</h1>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
const initElements:any = document.querySelector('#app-dashboard')
export default defineComponent({
  name: 'TheHistoAppDashboard',
  components: {
  },
  data () {
    return {
      sharedProps: store.props
    }
  },
  created () {
    if (initElements !== null) {
      this.sharedProps.ajaxurlFilter = initElements.dataset.ajaxurlFilter
      this.sharedProps.ajaxurlPartners = initElements.dataset.ajaxurlPartners
      this.sharedProps.ajaxurlSignalementsNosuivi = initElements.dataset.ajaxurlSignalementsNosuivi
      this.sharedProps.ajaxurlSignamentsPerTerritoire = initElements.dataset.ajaxurlSignamentsPerTerritoire
      this.sharedProps.ajaxurlConnectionsEsabora = initElements.dataset.ajaxurlConnectionsEsabora
    } else {
      alert('Error while loading dashboard')
    }
  }
})
</script>

<style>
  .histo-dashboard {
    background-color: '#FFF';
  }
  .histo-app-front-stats h1 {
    text-align: center;
    color: var(--blue-france-sun-113-625);
  }
</style>
