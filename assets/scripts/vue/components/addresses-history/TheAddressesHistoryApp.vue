
<template>
  <div id="histo-app-addresses-history-view">
    <AddressesHistoryHeader @change="handleViewChange"/>
    <section v-if="sharedState.loadingList" class="loading fr-m-10w fr-text--center">
      <h2 class="fr-text--light" v-if="!sharedState.hasErrorLoading">Chargement de la liste...</h2>
      <h2 class="fr-text--light" v-if="sharedState.hasErrorLoading">Erreur lors du chargement de la liste.</h2>
      <p v-if="sharedState.hasErrorLoading">Veuillez recharger la page ou nous prévenir via le formulaire de contact.</p>
    </section>
    <AddressesHistoryMap v-else-if="sharedState.viewMode === 'map'"/>
    <AddressesHistoryList v-else-if="sharedState.viewMode === 'list'"
      @filtersChange="handleFilters"
      @territoryChange="handleTerritoryChange"
      />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import { requests } from './requests'
import { /*handleQueryParameter, */handleAddressesShared, handleSettings, handleTerritoryChange, handleFilters, addQueryParameter/*, buildUrl, clearScreen*/ } from './utils/appUtils'

import AddressesHistoryHeader from './components/AddressesHistoryHeader.vue'
import AddressesHistoryMap from './components/AddressesHistoryMap.vue'
import AddressesHistoryList from './components/AddressesHistoryList.vue'

const initElements:any = document.querySelector('#app-addresses-history-view')

export default defineComponent({
  name: 'TheAddressesHistoryApp',
  components: {
    AddressesHistoryHeader,
    AddressesHistoryMap,
    AddressesHistoryList,
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

        this.sharedProps.ajaxurlAddresses = initElements.dataset.ajaxurl
        this.sharedProps.baseAjaxUrlAddresses = initElements.dataset.ajaxurl
        this.sharedProps.ajaxurlSettings = initElements.dataset.ajaxurlSettings
        this.sharedProps.ajaxurlExportCsv = initElements.dataset.ajaxurlExportCsv
        this.sharedProps.platformName = initElements.dataset.platformName
        if (!reset) {
          // handleQueryParameter(this)
        }

        // buildUrl(this, initElements.dataset.ajaxurl)
        requests.getSettings(this.handleSettings)
        requests.getAddresses(this.handleAddresses, { signal: this.abortRequest?.signal })
      } else {
        this.sharedState.hasErrorLoading = true
      }
    },
    handleSettings (requestResponse: any) {
      this.sharedState.loadingList = false
      handleSettings(this, requestResponse)
    },
    handleAddresses (requestResponse: any) {
      handleAddressesShared(this, requestResponse)
    },
    handleTerritoryChange (value: any) {
      handleTerritoryChange(this, value)
    },
    /*handleClickReset () {
      console.log('handleClickReset')
      this.init(true)
    },*/
    handleFilters () {
      console.log('handleFilters')
      handleFilters(this)
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
