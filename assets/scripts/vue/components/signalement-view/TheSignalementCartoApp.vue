
<template>
  <div id="histo-app-signalement-carto">
    <div class="fr-grid-row">
      <div class="fr-col-2 filter-container-carto">
        <SignalementViewFilters
            :shared-props="sharedProps"
            @change="handleFilters"
            @changeTerritory="handleTerritoryChange"
            @clickReset="handleClickReset"
            :layout="'vertical'"
        />
      </div>
      <div class="fr-col-10">
        <SignalementViewCarto
          :api-url="sharedProps.ajaxurlSignalement"
          :token="sharedProps.token"
          formSelector="#signalement-view-filters"/>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import { requests } from './requests'
import SignalementViewFilters from './components/SignalementViewFilters.vue'
import SignalementViewCarto from './components/SignalementViewCarto.vue'
import { handleQueryParameter, handleSettings, handleTerritoryChange, handleSignalementsShared, handleFilters, buildUrl } from './utils/signalementUtils'

const initElements:any = document.querySelector('#app-signalement-carto')

export default defineComponent({
  name: 'TheSignalementCartoApp',
  components: {
    SignalementViewFilters,
    SignalementViewCarto
  },
  data () {
    return {
      sharedProps: store.props,
      sharedState: store.state,
      loadingList: true,
      hasErrorLoading: false,
      abortRequest: null as AbortController | null
    }
  },
  created () {
    this.init()
  },
  methods: {
    init (reset: boolean = false) {
      if (initElements !== null) {
        if (this.abortRequest) {
          this.abortRequest?.abort()
        }
        this.abortRequest = new AbortController()

        this.sharedProps.ajaxurlSignalement = initElements.dataset.ajaxurl
        this.sharedProps.ajaxurlSettings = initElements.dataset.ajaxurlSettings
        this.sharedProps.token = initElements.dataset.token
        if (!reset) {
          handleQueryParameter(this)
        }

        buildUrl(this, initElements.dataset.ajaxurl)
        requests.getSettings(this.handleSettings)
        requests.getSignalements(this.handleSignalements, { signal: this.abortRequest?.signal })
      } else {
        this.hasErrorLoading = true
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
    handleSignalements (requestResponse: any) {
      handleSignalementsShared(this, requestResponse)
    },
    handleFilters () {
      handleFilters(this, initElements.dataset.ajaxurl)
    }
  }
})

</script>

<style>
#histo-app-signalement-carto .fr-container--fluid {
  overflow: visible;
}
.filter-container-carto {
  overflow: auto;
  height: 100vh;
}
</style>
