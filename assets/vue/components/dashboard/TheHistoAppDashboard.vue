<template>
  <div
    class="histo-app-dashboard"
    :data-ajaxurl-filter="sharedProps.ajaxurlFilter"
    :data-ajaxurl-partners="sharedProps.ajaxurlPartners"
    :data-ajaxurl-signalements-nosuivi="sharedProps.ajaxurlSignalementsNosuivi"
    :data-ajaxurl-signalements-per-territoire="sharedProps.ajaxurlSignamentsPerTerritoire"
    :data-ajaxurl-connections-esabora="sharedProps.ajaxurlConnectionsEsabora"
    >

    <TheHistoDashboardHeader />

    <div class="fr-px-3w">
      <div class="fr-grid-row fr-grid-row--gutters fr-mb-1w">
        <div class="fr-col fr-col-md-9">
          <h1>Bonjour {{ sharedState.user.prenom }}</h1>
          <p>Bienvenue sur votre tableau de bord !</p>
        </div>
        <div v-if="sharedState.user.isAdmin" class="fr-col fr-col-md-3">
          <HistoSelect
            id="filter-territoires"
            v-model="sharedState.filters.territory"
            @update:modelValue="handleChangeTerritoire"
            inner-label="Territoire"
            :option-items=sharedState.territories
            />
        </div>
      </div>

      <TheHistoDashboardCards />

      <TheHistoDashboardTables v-if="sharedState.user.isAdmin || sharedState.user.isResponsableTerritoire" />
    </div>

  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import HistoSelect from '../common/HistoSelect.vue'
import TheHistoDashboardHeader from './TheHistoDashboardHeader.vue'
import TheHistoDashboardCards from './TheHistoDashboardCards.vue'
import TheHistoDashboardTables from './TheHistoDashboardTables.vue'
const initElements:any = document.querySelector('#app-dashboard')
export default defineComponent({
  name: 'TheHistoAppDashboard',
  components: {
    HistoSelect,
    TheHistoDashboardHeader,
    TheHistoDashboardCards,
    TheHistoDashboardTables
  },
  data () {
    return {
      sharedState: store.state,
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
  },
  methods: {
    handleChangeTerritoire () {
      console.log('refresh')
    }
  }
})
</script>

<style>
  div.fr-col-12.fr-col-md-9.fr-col-lg-10 {
    background-color: #F6F6F6;
  }
</style>
