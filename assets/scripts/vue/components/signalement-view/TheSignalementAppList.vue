
<template>
  <SignalementViewModalEditSearch/>
  <SignalementViewModalSaveSearch
    @savedSearchSuccess="onSearchSaved"
  />
  <div v-if="classNameDeleteConfirmation.length > 0" class="fr-alert fr-alert--sm" :class="classNameDeleteConfirmation">
    <p>{{ messageDeleteConfirmation }}</p>
  </div>
  <div id="histo-app-signalement-view">
    <section class="fr-background--white" :style="'block'">
      <div class="fr-grid-row fr-p-3w fr-pb-6w' fr-container-sml">
        <div class="fr-col-12">
          <div class="fr-grid-row fr-grid-row--top fr-mb-2w">
            <div class="fr-col-12 fr-col-lg-8">
              <h1 class="fr-h2">Liste des signalements</h1>
            </div>
            <div class="fr-col-12 fr-col-lg-4 fr-grid-row">
              <div class="fr-col-auto">
                <button
                  data-fr-opened="false"
                  aria-controls="modal-edit-search"
                  class="fr-btn fr-btn--secondary fr-icon-settings-5-line fr-mr-1w"
                  :disabled="sharedState.savedSearches.length === 0"
                ></button>
              </div>
              <div class="fr-col">
                <HistoSelect
                  :key="sharedState.savedSearchSelectKey"
                  id="filter-save-search"
                  v-model="sharedState.selectedSavedSearchId"
                  @update:modelValue="applySavedSearch"
                  title="Mes recherches sauvegardées"
                  :option-items="sharedState.savedSearches"
                  :placeholder="sharedState.savedSearches.length > 0 
                    ? 'Mes recherches sauvegardées' 
                    : 'Aucune recherche sauvegardée'"
                  ref="savedSearchSelect"
                />
              </div>
            </div>
          </div>
          <div class="fr-container--fluid" role="search">
            <SignalementViewFilters
                :shared-props="sharedProps"
                @change="handleFilters"
                @changeTerritory="handleTerritoryChange"
                @clickReset="handleClickReset"
                :layout="'horizontal'"
                :viewType="'list'"
            />
          </div>
        </div>
      </div>
    </section>
    <section v-if="sharedState.loadingList" class="loading fr-m-10w fr-text--center">
      <h2 class="fr-text--light" v-if="!sharedState.hasErrorLoading">Chargement de la liste...</h2>
      <h2 class="fr-text--light" v-if="sharedState.hasErrorLoading">Erreur lors du chargement de la liste.</h2>
      <p v-if="sharedState.hasErrorLoading">Veuillez recharger la page ou nous prévenir <a :href="sharedProps.ajaxurlContact">via le formulaire de contact</a>.</p>
    </section>
    <section v-else class="fr-col-12 fr-background-alt--blue-france fr-mt-0">
        <div :class="['fr-p-3w', 'fr-container-sml']">
          <SignalementListHeader
              :total="sharedState.signalements.pagination.total_items"
              @change="handleOrderChange"/>
          <SignalementListCards
              :list="sharedState.signalements.list"
              @deleteSignalementItem="deleteItem" />
          <SignalementListPagination
              :pagination="sharedState.signalements.pagination"
              @changePage="handlePageChange"/>
        </div>
    </section>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import { requests } from './requests'
import { SignalementItem } from './interfaces/signalementItem'
import SignalementViewFilters from './components/SignalementViewFilters.vue'
import SignalementListHeader from './components/SignalementListHeader.vue'
import SignalementListCards from './components/SignalementListCards.vue'
import SignalementListPagination from './components/SignalementListPagination.vue'
import SignalementViewModalEditSearch from './components/SignalementViewModalEditSearch.vue'
import SignalementViewModalSaveSearch from './components/SignalementViewModalSaveSearch.vue'
import HistoSelect from '../common/HistoSelect.vue'
import { handleQueryParameter, handleSettings, handleTerritoryChange, handleSignalementsShared, handleFilters, addQueryParameter, removeQueryParameter, buildUrl, clearScreen, applySavedSearch } from './utils/signalementUtils'

const initElements:any = document.querySelector('#app-signalement-view')

export default defineComponent({
  name: 'TheSignalementAppList',
  components: {
    SignalementListHeader,
    SignalementViewFilters,
    SignalementListCards,
    SignalementListPagination,
    SignalementViewModalEditSearch,
    SignalementViewModalSaveSearch,
    HistoSelect
  },
  data () {
    return {
      sharedProps: store.props,
      sharedState: store.state,
      messageDeleteConfirmation: '',
      classNameDeleteConfirmation: '',
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
        this.sharedProps.ajaxurlRemoveSignalement = initElements.dataset.ajaxurlRemoveSignalement
        this.sharedProps.ajaxurlSettings = initElements.dataset.ajaxurlSettings
        this.sharedProps.ajaxurlExportCsv = initElements.dataset.ajaxurlExportCsv
        this.sharedProps.ajaxurlContact = initElements.dataset.ajaxurlContact
        this.sharedProps.ajaxurlSaveSearch = initElements.dataset.ajaxurlSaveSearch
        this.sharedProps.csrfSaveSearch = initElements.dataset.csrfSaveSearch
        this.sharedProps.ajaxurlDeleteSearch = initElements.dataset.ajaxurlDeleteSearch
        this.sharedProps.csrfDeleteSearch = initElements.dataset.csrfDeleteSearch
        this.sharedProps.ajaxurlEditSearch = initElements.dataset.ajaxurlEditSearch
        this.sharedProps.csrfEditSearch = initElements.dataset.csrfEditSearch
        this.sharedProps.platformName = initElements.dataset.platformName
        if (!reset) {
          handleQueryParameter(this)
        }

        buildUrl(this, initElements.dataset.ajaxurl)
        requests.getSettings(this.handleSettings)
        requests.getSignalements(this.handleSignalements, { signal: this.abortRequest?.signal })
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
    handleSignalements (requestResponse: any) {
      handleSignalementsShared(this, requestResponse)
    },
    handlePageChange (pageNumber: number) {
      clearScreen(this)
      const url = new URL(window.location.toString())
      url.searchParams.set('page', pageNumber.toString())
      window.history.replaceState({}, '', url)
      addQueryParameter(this, 'page', pageNumber.toString())
      buildUrl(this, initElements.dataset.ajaxurl)
      requests.getSignalements(this.handleSignalements)
    },
    handleOrderChange () {
      clearScreen(this)
      const [field, direction] = this.sharedState.input.order.split('-')
      removeQueryParameter(this, 'page')
      removeQueryParameter(this, 'sortBy')
      removeQueryParameter(this, 'direction')

      const url = new URL(window.location.toString())
      url.searchParams.delete('page')
      url.searchParams.set('sortBy', field)
      url.searchParams.set('direction', direction)
      window.history.pushState({}, '', url.toString())

      addQueryParameter(this, 'sortBy', field)
      addQueryParameter(this, 'direction', direction)
      buildUrl(this, initElements.dataset.ajaxurl)
      requests.getSignalements(this.handleSignalements)
    },
    handleFilters () {
      handleFilters(this, initElements.dataset.ajaxurl)
    },    
    handleDelete (requestResponse: any) {
      this.messageDeleteConfirmation =
          requestResponse.data.status === 200
            ? requestResponse.data.message
            : 'Une erreur s\'est produite lors de la suppression. Veuillez réessayer plus tard.'
      this.classNameDeleteConfirmation =
          requestResponse.data.status === 200
            ? 'fr-alert--success'
            : 'fr-alert--error'

      buildUrl(this, initElements.dataset.ajaxurl)
      requests.getSignalements(this.handleSignalements)
    },
    onSearchSaved(message: string, className: string ) {
      this.messageDeleteConfirmation = message
      this.classNameDeleteConfirmation = className
    },
    applySavedSearch(value: string) {
      applySavedSearch(this, value)
    },
    async deleteItem (item: SignalementItem|null) {
      clearScreen(this)
      if (!item) {
        return
      }
      await requests.deleteSignalement(
        item.uuid,
        item.csrfToken,
        this.handleDelete
      )
    }
  }
})

</script>

<style>
#histo-app-signalement-view .fr-container--fluid {
  overflow: visible;
}
</style>
