
<template>
  <div id="histo-app-addresses-history-view">
    <section class="fr-background--white">
      <AddressesHistoryHeader @change="handleViewChange"/>
      <div class="fr-grid-row fr-p-3w fr-pb-6w fr-container-sml">
        <div class="fr-col-12">
          <div class="fr-grid-row fr-grid-row--top fr-mb-2w">
            <div class="fr-col-12 fr-col-lg-8">
              <h1 class="fr-h2">Historique des événements par adresse</h1>
            </div>
          </div>
          <div class="fr-container--fluid" role="search">
            <div class="fr-skiplinks">
              <a class="fr-link fr-link--icon-right fr-icon-arrow-down-line" href="#list-signalements" aria-label="Passer directement à la liste des signalements">Passer directement à la liste des signalements</a>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section v-if="sharedState.loadingList" class="loading fr-m-10w fr-text--center">
      <h2 class="fr-text--light" v-if="!sharedState.hasErrorLoading">Chargement de la liste...</h2>
      <h2 class="fr-text--light" v-if="sharedState.hasErrorLoading">Erreur lors du chargement de la liste.</h2>
      <p v-if="sharedState.hasErrorLoading">Veuillez recharger la page ou nous prévenir via le formulaire de contact.</p>
    </section>
    <section v-else class="fr-col-12 fr-background-alt--blue-france fr-mt-0">
        <div :class="['fr-p-3w', 'fr-container-sml']">
            <div v-if="sharedState.viewMode === 'map'">
                Carte
            </div>
            <div v-if="sharedState.viewMode === 'list'">
                Liste
            </div>
        </div>
    </section>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import { requests } from './requests'
import { /*handleQueryParameter, */handleSettings, handleTerritoryChange, /*handleFilters,*/ addQueryParameter/*, buildUrl, clearScreen*/ } from './utils/appUtils'

import AddressesHistoryHeader from './components/AddressesHistoryHeader.vue'

const initElements:any = document.querySelector('#app-addresses-history-view')

export default defineComponent({
  name: 'TheAddressesHistoryApp',
  components: {
    AddressesHistoryHeader,
  },
  data () {
    return {
      sharedProps: store.props,
      sharedState: store.state,
      abortRequest: null as AbortController | null
    }
  },
  created () {
    this.init()
  },
  methods: {
    init (reset: boolean = false) {
      if (initElements !== null) {
        this.sharedState.hasErrorLoading = false
        if (this.abortRequest) {
          this.abortRequest?.abort()
        }
        this.abortRequest = new AbortController()

        this.sharedProps.ajaxurlSignalement = initElements.dataset.ajaxurl
        this.sharedProps.baseAjaxUrlSignalement = initElements.dataset.ajaxurl
        this.sharedProps.ajaxurlSettings = initElements.dataset.ajaxurlSettings
        this.sharedProps.ajaxurlExportCsv = initElements.dataset.ajaxurlExportCsv
        this.sharedProps.platformName = initElements.dataset.platformName
        if (!reset) {
          // handleQueryParameter(this)
        }

        // buildUrl(this, initElements.dataset.ajaxurl)
        requests.getSettings(this.handleSettings)
        // requests.getSignalements(this.handleSignalements, { signal: this.abortRequest?.signal })
      } else {
        this.sharedState.hasErrorLoading = true
      }
    },
    handleSettings (requestResponse: any) {
      handleSettings(this, requestResponse)
    },
    handleTerritoryChange (value: any) {
      handleTerritoryChange(this, value)
    },
    handleClickReset () {
      this.init(true)
    },
    handlePageChange (pageNumber: number) {
      // clearScreen(this)
      const url = new URL(window.location.toString())
      url.searchParams.set('page', pageNumber.toString())
      window.history.replaceState({}, '', url)
      addQueryParameter(this, 'page', pageNumber.toString())
      // buildUrl(this, initElements.dataset.ajaxurl)
      // requests.getSignalements(this.handleSignalements)
    },
    handleFilters () {
      // handleFilters(this, initElements.dataset.ajaxurl)
    },
    handleViewChange (pageName: string) {
      this.sharedState.viewMode = pageName
    }
  }
})

</script>

<style>
#histo-app-signalement-view .fr-container--fluid {
  overflow: visible;
}
</style>
