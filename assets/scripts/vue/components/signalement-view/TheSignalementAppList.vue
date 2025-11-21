
<template>
  <div v-if="classNameDeleteConfirmation.length > 0" class="fr-alert fr-alert--sm" :class="classNameDeleteConfirmation">
    <p>{{ messageDeleteConfirmation }}</p>
  </div>
  <dialog aria-labelledby="modal-delete-search-title" id="modal-delete-search" class="fr-modal">
    <div class="fr-container fr-container--fluid fr-container-lg">
      <div class="fr-grid-row fr-grid-row--center">
        <div class="fr-col-12 fr-col-md-10 fr-col-lg-8">
          <div class="fr-modal__body">
            <div class="fr-modal__header">
              <button type="button" class="fr-btn--close fr-btn" aria-controls="modal-delete-search">Fermer</button>
            </div>
            <div class="fr-modal__content">
              <h1 id="modal-delete-search-title" class="fr-modal__title">
                Mes recherches sauvegardées
              </h1>

              <div v-if="sharedState.savedSearches.length === 0">
                <p>Aucune recherche sauvegardée</p>
              </div>

              <div v-else>
                <div v-for="search in sharedState.savedSearches" :key="search.Id" class="fr-mb-4v">
                  <h2 class="fr-h4 fr-mb-2v">{{ search.Text }}</h2>

                  <div class="fr-grid-row fr-grid-row--bottom fr-mb-2v">
                    <div class="fr-col-9">
                      <div class="fr-input-group fr-mr-2v">
                        <label class="fr-label">Nom de la recherche</label>
                        <input type="text" class="fr-input" v-model="search.NewName" :placeholder="search.Text" :maxlength="50"/>
                      </div>
                    </div>
                    <div class="fr-col-3 fr-text-right">
                      <button class="fr-btn fr-btn--icon-left fr-btn--secondary fr-icon-edit-line" @click="editSavedSearch(search.Id, search.NewName)">
                        Modifier le nom
                      </button>
                    </div>
                  </div>

                  <!-- Filtres / tags non cliquables -->
                  <div class="fr-mb-2v">
                    <label class="fr-label">Filtres de la recherche</label>
                    <div class="fr-tags-group">
                      <span v-for="(value, key) in search.Params" :key="key" class="fr-tag fr-tag--sm fr-tag--dismiss">
                        {{ getBadgeFilterLabel(key, value) }}
                      </span>
                    </div>
                  </div>

                  <button class="fr-btn fr-btn--icon-left fr-btn--secondary fr-icon-delete-line"
                          @click="deleteSavedSearch(search.Id)">
                    Supprimer la recherche
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </dialog>
  <div id="histo-app-signalement-view">
    <section class="fr-background--white" :style="'block'">
      <div class="fr-grid-row fr-p-3w fr-pb-6w' fr-container-sml">
        <div class="fr-col-12">
          <div class="fr-grid-row fr-grid-row--middle fr-mb-2w">
            <div class="fr-col">
              <h1 class="fr-h2">Liste des signalements</h1>
            </div>
            <div class="fr-col fr-grid-row fr-grid-row--right fr-grid-row--middle fr-gap-2v">
              <button
                data-fr-opened="false"
                aria-controls="modal-delete-search"
                class="fr-btn fr-btn--secondary fr-icon-settings-5-line"
                :disabled="sharedState.savedSearches.length === 0"
              ></button>
              <HistoSelect
                class="fr-ml-2v"
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
          <div class="fr-container--fluid" role="search">
            <SignalementViewFilters
                :shared-props="sharedProps"
                @change="handleFilters"
                @changeTerritory="handleTerritoryChange"
                @clickReset="handleClickReset"
                @clickSaveSearch="handleClickSaveSearch"
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
import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'
import HistoSelect from '../common/HistoSelect.vue'
import { handleQueryParameter, handleSettings, handleTerritoryChange, handleSignalementsShared, handleFilters, addQueryParameter, removeQueryParameter, buildUrl, clearScreen } from './utils/signalementUtils'
import { buildBadge } from './services/badgeFilterLabelBuilder'

const initElements:any = document.querySelector('#app-signalement-view')

export default defineComponent({
  name: 'TheSignalementAppList',
  components: {
    SignalementListHeader,
    SignalementViewFilters,
    SignalementListCards,
    SignalementListPagination,
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
      // this.sharedState.selectedSavedSearchId = null
    },
    handleSettings (requestResponse: any) {
      handleSettings(this, requestResponse)
      this.$nextTick(() => {
        // refresh HistoSelect via ref
        const selectRef = this.$refs.savedSearchSelect as any | undefined
        if (selectRef?.refreshDisplayedItems) {
          selectRef.refreshDisplayedItems(this.sharedState.selectedSavedSearchId)
        }
      })
    },
    handleTerritoryChange (value: any) {
      handleTerritoryChange(this, value)
    },
    handleClickReset () {
      this.init(true)
    },
    handleClickSaveSearch (payload: { name: string; params: any }) {
      requests.saveSearch(payload, this.sharedProps.csrfSaveSearch, this.handleSearchSaved)
    },
    // handleClickDeleteSearch(id: string) {
    //   requests.deleteSearch(id, this.sharedProps.csrfDeleteSearch, this.handleSearchDeleted)
    // },
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
      this.sharedState.selectedSavedSearchId = null
      handleFilters(this, initElements.dataset.ajaxurl)
    },    
    handleDelete (requestResponse: any) {
      this.messageDeleteConfirmation =
          requestResponse.status === 200
            ? requestResponse.message
            : 'Une erreur s\'est produite lors de la suppression. Veuillez réessayer plus tard.'
      this.classNameDeleteConfirmation =
          requestResponse.status === 200
            ? 'fr-alert--success'
            : 'fr-alert--error'

      buildUrl(this, initElements.dataset.ajaxurl)
      requests.getSignalements(this.handleSignalements)
    },
    handleSearchSaved (requestResponse: any) {
      this.messageDeleteConfirmation = requestResponse.data.message
      this.classNameDeleteConfirmation =
          requestResponse.status === 200
            ? 'fr-alert--success'
            : 'fr-alert--error'
      if (requestResponse.status === 200 && requestResponse.data?.data?.savedSearch) {
        const saved = requestResponse.data.data.savedSearch
        const newOption = new HistoInterfaceSelectOption()
        newOption.Id = saved.id.toString()
        newOption.Text = saved.name
        newOption.Params = saved.params
        this.sharedState.savedSearches.push(newOption)
        this.sharedState.selectedSavedSearchId = newOption.Id
        this.sharedState.savedSearchSelectKey++
      }
    },
    handleSearchDeleted (requestResponse: any, id: string = '') {
      this.messageDeleteConfirmation = requestResponse.data.message
      this.classNameDeleteConfirmation =
          requestResponse.status === 200
            ? 'fr-alert--success'
            : 'fr-alert--error'
      if (requestResponse.status === 200) {
        const updatedSearches = this.sharedState.savedSearches.filter(s => s.Id !== id);
        const selectedId = this.sharedState.selectedSavedSearchId === id ? '' : this.sharedState.selectedSavedSearchId;
        this.sharedState.savedSearches = updatedSearches;
        this.sharedState.selectedSavedSearchId = selectedId;
        this.sharedState.savedSearchSelectKey++
      }
    },
    handleSearchEdited (requestResponse: any, id: string = '', newName: string = '') {
      this.messageDeleteConfirmation = requestResponse.data.message
      this.classNameDeleteConfirmation =
          requestResponse.status === 200
            ? 'fr-alert--success'
            : 'fr-alert--error'
      if (requestResponse.status === 200) {
        const item = this.sharedState.savedSearches.find(s => s.Id === id)
        if (item) {
          item.Text = newName       // ce que le HistoSelect doit afficher
          item.NewName = newName    // ce que le champ input doit afficher
        }

        this.sharedState.savedSearchSelectKey++
      }
    },
    applySavedSearch(value: string) {
      if (!value) {
        return
      }

      const selected = this.sharedState.savedSearches.find(s => s.Id === value)
      if (!selected) {
        console.warn('Saved search introuvable.')
        return
      }

      // this.resetFilters()

      const params = selected.Params as Record<string, any>
      const filters = this.sharedState.input.filters as Record<string, any>
      for (const key in params) {
        filters[key] = params[key]
      }

      // if (typeof this.onChange === 'function') {
        this.handleFilters()
      // }
      this.sharedState.selectedSavedSearchId = value
    },
    deleteSavedSearch(id: string) {
      requests.deleteSearch(id, this.sharedProps.csrfDeleteSearch, this.handleSearchDeleted)
    },
    editSavedSearch(id: string, newName: string) {
      requests.editSearch(id, newName, this.sharedProps.csrfEditSearch, this.handleSearchEdited)
    },
    getBadgeFilterLabel (key: string, value: any) {
      return buildBadge(key, value)
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
